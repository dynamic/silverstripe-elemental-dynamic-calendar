<?php

namespace Dynamic\Elements\Calendar\Elements;

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

        // Get events feed from the Calendar page with limit and category filtering
        $events = $calendar->getEventsFeed($this->Limit, $this->Categories());

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
        if ($this->getEvents()->count() > 0) {
            $ct = $this->getEvents()->count();
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

