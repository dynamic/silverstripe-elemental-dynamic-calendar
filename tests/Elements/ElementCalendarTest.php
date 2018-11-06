<?php

namespace Dynamic\Elements\Calendar\Tests\Elements;

use Dynamic\Elements\Calendar\Elements\ElementCalendar;
use SilverStripe\Dev\SapphireTest;

/**
 * Class ElementCalendarTest
 * @package Dynamic\Elements\Calendar\Tests\Elements
 */
class ElementCalendarTest extends SapphireTest
{
    /**
     * @var string
     */
    protected static $fixture_file = '../fixtures.yml';

    /**
     * Tests getType().
     */
    public function testGetType()
    {
        $object = $this->objFromFixture(ElementCalendar::class, 'one');
        $this->assertEquals($object->getType(), 'Calendar');
    }
}
