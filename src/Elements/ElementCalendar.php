<?php

namespace Dynamic\Elements\Calendar\Elements;

use DNADesign\Elemental\Models\BaseElement;
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
     * @var string
     */
    private static $icon = 'vendor/dnadesign/silverstripe-elemental/images/base.svg';

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
    private static $db = array(
        'Limit' => 'Int',
        'Content' => 'HTMLText',
    );

    /**
     * @var array
     */
    private static $defaults = array(
        'Limit' => 3,
    );

    /**
     * @return Calendar
     */
    public function getCalendar()
    {
        return Calendar::get()->first();
    }

    /**
     * @return \SilverStripe\ORM\ArrayList|\SilverStripe\ORM\DataList
     */
    public function getEvents()
    {
        return Calendar::upcoming_events()->limit($this->Limit);
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
