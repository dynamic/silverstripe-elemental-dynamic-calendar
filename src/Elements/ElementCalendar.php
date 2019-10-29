<?php

namespace Dynamic\Elements\Calendar\Elements;

use DNADesign\Elemental\Models\BaseElement;
use Dynamic\Calendar\Controller\CalendarController;
use Dynamic\Calendar\Page\Calendar;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Class ElementCalendar
 * @package Dynamic\Elements\Calendar
 *
 * @property int $Limit
 * @property string $Content
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
    private static $defaults = [
        'Limit' => 3,
    ];

    /**
     * @return $this
     */
    protected function setEvents()
    {
        $events = $this->CalendarID
            ? CalendarController::create($this->Calendar())->getEvents()
            : CalendarController::create($this->Calendar())->setDefaultFilter(true)->getEvents();

        $this->extend('updateSetEvents', $events);

        if ($this->Limit > 0) {
            $events = $events->limit($this->Limit);
        }

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
