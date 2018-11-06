<?php

namespace Dynamic\Elements\Calendar\Elements;

use DNADesign\Elemental\Models\BaseElement;
use Dynamic\Calendar\Page\Calendar;

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
     * @return DBHTMLText
     */
    public function ElementSummary()
    {
        return DBField::create_field('HTMLText', $this->Content)->Summary(20);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return _t(__CLASS__ . '.BlockType', 'Calendar');
    }

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
}
