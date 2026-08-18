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
use SilverStripe\ORM\ArrayList;
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
                    $fields->dataFieldByName('CalendarID'),
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
     * How far ahead (in months) setEvents() looks when no explicit window is
     * involved. Bounds the recurring-event expansion: with no window the feed
     * expanded every recurring event across a multi-year default range just to
     * render a summary. 0 disables the bound (pre-4.1 behaviour).
     *
     * @config
     * @var int
     */
    private static int $events_window_months = 6;

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

        // Bound the window so the feed only expands occurrences it can show.
        // Previously both date arguments were omitted, so every uncached call
        // (including provideBlockSchema() for each block in the CMS editor)
        // materialised the calendar's entire event corpus to display a few.
        $windowMonths = (int) $this->config()->get('events_window_months');
        $fromDate = $windowMonths > 0 ? Carbon::today() : null;
        $toDate = $windowMonths > 0 ? Carbon::today()->addMonths($windowMonths)->endOfMonth() : null;

        $events = $calendar->getEventsFeed($this->Limit, $this->Categories(), $fromDate, $toDate);

        $this->extend('updateSetEvents', $events);

        $this->events = $events;

        return $this;
    }

    /**
     * @return \SilverStripe\ORM\ArrayList|\SilverStripe\ORM\DataList
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

    /**
     * @return string
     */
    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Calendar');
    }
}
