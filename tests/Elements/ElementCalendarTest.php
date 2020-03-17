<?php

namespace Dynamic\Elements\Calendar\Tests\Elements;

use Dynamic\Calendar\Model\Category;
use Dynamic\Calendar\Page\Calendar;
use Dynamic\Calendar\Page\EventPage;
use Dynamic\Elements\Calendar\Elements\ElementCalendar;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Versioned\Versioned;

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
     *
     */
    protected function setUp()
    {
        parent::setUp();

        $start = strtotime('yesterday');

        $dates = [
            date('Y-m-d', $start),
            date('Y-m-d', strtotime('+ 3 days', $start)),
            date('Y-m-d', strtotime('+ 5 days', $start)),
            date('Y-m-d', strtotime('+ 9 days', $start)),
            date('Y-m-d', strtotime('+ 12 days', $start)),
        ];

        $createEvent = function ($date, $calendar) {
            $event = EventPage::create();

            $event->Title = "Event {$date}";
            $event->StartDate = $date;
            $event->ParentID = $calendar->ID;

            $event->writeToStage(Versioned::DRAFT);
            $event->publishSingle();

            return $event;
        };

        $calendar = $this->objFromFixture(Calendar::class, 'one');
        $calendarTwo = $this->objFromFixture(Calendar::class, 'two');
        $calendarThree = $this->objFromFixture(Calendar::class, 'three');
        $calendarFour = $this->objFromFixture(Calendar::class, 'four');

        foreach ($dates as $date) {
            $createEvent($date, $calendar);
            $createEvent($date, $calendarTwo);
            $createEvent($date, $calendarThree);
            $createEvent($date, $calendarFour);
        }
    }

    /**
     * Tests getType().
     */
    public function testGetType()
    {
        $object = $this->objFromFixture(ElementCalendar::class, 'one');
        $this->assertEquals($object->getType(), 'Calendar');
    }

    /**
     *
     */
    public function testGetEventsNoCalendar()
    {
        /** @var ElementCalendar $element */
        $element = $this->objFromFixture(ElementCalendar::class, 'one');
        //Default limit is 3
        $this->assertEquals(3, $element->getEvents()->count());

        /** @var ElementCalendar $elementTwo */
        $elementTwo = $this->objFromFixture(ElementCalendar::class, 'two');
        $this->assertEquals(16, $elementTwo->getEvents()->count());
    }

    /**
     *
     */
    public function testGetEventsCalendar()
    {
        /** @var ElementCalendar $element */
        $element = $this->objFromFixture(ElementCalendar::class, 'three');
        $events = $element->getEvents();

        $this->assertEquals(3, $events->count());

        foreach ($events as $event) {
            $this->assertEquals($element->CalendarID, $event->ParentID);
        }
    }

    /**
     * @throws \Exception
     */
    public function testGetEventsCategory()
    {
        $parent = $this->objFromFixture(Calendar::class, 'three');

        $events = EventPage::get()->filter('ParentID', $parent->ID);
        $categoryOne = $this->objFromFixture(Category::class, 'one');
        $categoryTwo = $this->objFromFixture(Category::class, 'two');

        /**
         * @var int $key
         * @var EventPage $event
         */
        foreach ($events as $key => $event) {
            $categories = $event->Categories();

            if ($key % 2 == 0) {
                $categories->add($categoryOne);
            } else {
                $categories->add($categoryTwo);
            }
        }

        /** @var ElementCalendar $element */
        $element = $this->objFromFixture(ElementCalendar::class, 'four');

        $this->assertEquals(2, $element->getEvents()->count());
    }
}
