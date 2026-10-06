<?php

namespace Dynamic\Elements\Calendar\Elements;

use Carbon\Carbon;
use DNADesign\Elemental\Models\BaseElement;
use Dynamic\Calendar\Model\Category;
use Dynamic\Calendar\Page\Calendar;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldAddExistingAutocompleter;
use SilverStripe\Forms\GridField\GridFieldAddNewButton;
use SilverStripe\Model\List\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\ManyManyList;
use Symbiote\GridFieldExtensions\GridFieldAddExistingSearchButton;

/**
 * Class ElementCalendar
 * @package Dynamic\Elements\Calendar
 *
 * @property int $Limit
 * @property string $Content
 * @property int $CalendarID
 *
 * @method Calendar Calendar()
 * @method ManyManyList Categories()
 */
class ElementCalendar extends BaseElement
{
    /**
     * @var
     */
    private $events;

    /**
     * @var string
     */
    private static $icon = 'font-icon-p-event-alt';

    /**
     * @var string
     */
    private static $singular_name = 'Calendar Element';

    /**
     * @var string
     */
    private static $plural_name = 'Calendar Elements';

    /**
     * @var string
     */
    private static $table_name = 'ElementCalendar';

    /**
     * @var array
     */
    private static $db = [
        'Limit' => 'Int',
        'Content' => 'HTMLText',
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Calendar' => Calendar::class,
    ];

    /**
     * @var array
     */
    private static $many_many = [
        'Categories' => Category::class,
    ];

    /**
     * @var array
     */
    private static $defaults = [
        'Limit' => 3,
    ];

    /**
     * @return FieldList
     */
    public function getCMSFields()
    {
        $this->beforeUpdateCMSFields(function (FieldList $fields) {
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    DropdownField::create(
                        'CalendarID',
                        _t(__CLASS__ . '.CalendarLabel', 'Calendar'),
                        Calendar::get()->map('ID', 'Title')
                    )->setEmptyString(''),
                    $fields->dataFieldByName('Limit'),
                ],
                'Content'
            );

            /** @var GridField $categories */
            if ($categories = $fields->dataFieldByName('Categories')) {
                $config = $categories->getConfig();

                $config->removeComponentsByType([
                    GridFieldAddNewButton::class,
                    GridFieldAddExistingAutocompleter::class,
                ])->addComponents([
                    GridFieldAddExistingSearchButton::create(),
                ]);
            }
        });

        return parent::getCMSFields();
    }

    /**
     * How far ahead (in months) setEvents() looks when fetching the feed. Bounds the
     * recurring-event expansion: with no window the feed expanded every recurring event
     * across a multi-year default range, and the calendar's whole past event corpus,
     * just to render a summary. 0 disables the bound entirely (the pre-fix behaviour,
     * restored for sites that need it).
     *
     * @config
     * @var int
     */
    private static int $events_window_months = 6;

    /**
     * How many days before today the window reaches back. Calendar::getEventsFeed()
     * filters on StartDate only, so a window starting exactly today also drops an event
     * that is still running (StartDate < today <= EndDate). Reaching back a day and then
     * discarding anything that has already finished keeps running events visible without
     * letting a finished one take a display slot. 0 starts the window exactly today,
     * which excludes running events that began yesterday.
     *
     * @config
     * @var int
     */
    private static int $events_window_backfill_days = 1;

    /**
     * Set events using the Calendar's feed method
     *
     * @return $this
     */
    protected function setEvents()
    {
        $calendar = $this->Calendar();

        // If no calendar is set, try to use the current page if it's a Calendar
        if (!$calendar || !$calendar->exists()) {
            $currentPage = $this->getPage();
            if ($currentPage instanceof Calendar) {
                $calendar = $currentPage;
            }
        }

        if (!$calendar || !$calendar->exists()) {
            $this->events = ArrayList::create();
            return $this;
        }

        // Bound the window so the feed only expands occurrences it can show. Previously
        // both date arguments were omitted, so every uncached call (including
        // provideBlockSchema() for each block in the CMS editor) materialised the
        // calendar's entire event corpus to display a few.
        $windowMonths = (int) $this->config()->get('events_window_months');
        $backfillDays = max(0, (int) $this->config()->get('events_window_backfill_days'));

        if ($windowMonths > 0) {
            $fromDate = Carbon::today()->subDays($backfillDays);
            $toDate = Carbon::today()->addMonths($windowMonths)->endOfMonth();
        } else {
            $fromDate = null;
            $toDate = null;
        }

        // The feed's own limit is deliberately not used here: it is applied inside
        // getEventsFeed() before anything can inspect EndDate, so a finished event would
        // take a slot and under-fill the block. The window bounds the fetch, so dropping
        // that limit costs nothing and the limit is re-applied after the filter below.
        $events = $calendar->getEventsFeed(null, $this->Categories(), $fromDate, $toDate);

        if ($windowMonths > 0 && $backfillDays > 0) {
            $today = Carbon::today();
            $upcoming = ArrayList::create();

            foreach ($events as $event) {
                // Events whose end is unknown stay in the list; recurring occurrences carry
                // an instance-relative EndDate, so this comparison is not made against the
                // original event's (possibly years-old) end date.
                $endDate = $event->EndDate ?: $event->StartDate;

                if (!$endDate || Carbon::parse($endDate)->startOfDay()->gte($today)) {
                    $upcoming->push($event);
                }
            }

            $events = $upcoming;
        }

        if ($this->Limit > 0) {
            $events = $events->limit((int) $this->Limit);
        }

        $this->extend('updateSetEvents', $events);

        $this->events = $events;

        return $this;
    }

    /**
     * @return \SilverStripe\Model\List\ArrayList|\SilverStripe\ORM\DataList
     */
    public function getEvents()
    {
        if (!$this->events) {
            $this->setEvents();
        }

        return $this->events;
    }

    /**
     * @return DBHTMLText
     */
    public function getSummary()
    {
        $ct = $this->getEvents()->count();

        if ($ct > 0) {
            if ($ct == 1) {
                $label = ' event';
            } else {
                $label = ' events';
            }

            return DBField::create_field(
                'HTMLText',
                $ct . $label
            )->Summary(20);
        }

        return DBField::create_field('HTMLText', 'No events');
    }

    /**
     * @return array
     */
    protected function provideBlockSchema()
    {
        $blockSchema = parent::provideBlockSchema();
        $blockSchema['content'] = $this->getSummary();

        return $blockSchema;
    }

    private static string $class_description = 'Calendar';
}
