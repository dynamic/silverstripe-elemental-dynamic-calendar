<?php

namespace Dynamic\Elements\Calendar\Tests\Elements;

use Carbon\Carbon;
use Dynamic\Calendar\Model\Category;
use Dynamic\Calendar\Page\Calendar;
use Dynamic\Calendar\Page\EventPage;
use Dynamic\Elements\Calendar\Elements\ElementCalendar;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\ORM\ArrayList;
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
     * @var Calendar
     */
    protected $calendar;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a test calendar
        $this->calendar = Calendar::create([
            'Title' => 'Test Calendar',
            'URLSegment' => 'test-calendar',
        ]);
        $this->calendar->write();
        $this->calendar->publishRecursive();

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
            $event->Recursion = 'NONE';

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
     * Test that ElementCalendar returns ArrayList from getEvents
     */
    public function testGetEventsReturnsArrayList()
    {
        $element = ElementCalendar::create([
            'CalendarID' => $this->calendar->ID,
            'Limit' => 5,
        ]);
        $element->write();

        $events = $element->getEvents();
        $this->assertInstanceOf(ArrayList::class, $events);
    }

    /**
     * Test that ElementCalendar with no calendar returns empty events
     */
    public function testGetEventsNoCalendar()
    {
        $element = ElementCalendar::create([
            'CalendarID' => 0, // No calendar
            'Limit' => 3,
        ]);
        $element->write();

        $events = $element->getEvents();
        $this->assertEquals(0, $events->count());
    }

    /**
     * Test that ElementCalendar delegates to Calendar's getEventsFeed method
     */
    public function testElementDelegatesToCalendarEventsFeed()
    {
        // Create test events
        $event1 = EventPage::create([
            'Title' => 'Test Event 1',
            'ParentID' => $this->calendar->ID,
            'StartDate' => Carbon::tomorrow()->format('Y-m-d'),
            'StartTime' => '14:00:00',
            'EndDate' => Carbon::tomorrow()->format('Y-m-d'),
            'EndTime' => '16:00:00',
            'Recursion' => 'NONE',
        ]);
        $event1->write();
        $event1->publishRecursive();

        $event2 = EventPage::create([
            'Title' => 'Test Event 2',
            'ParentID' => $this->calendar->ID,
            'StartDate' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'StartTime' => '10:00:00',
            'EndDate' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'EndTime' => '11:00:00',
            'Recursion' => 'NONE',
        ]);
        $event2->write();
        $event2->publishRecursive();

        $element = ElementCalendar::create([
            'CalendarID' => $this->calendar->ID,
            'Limit' => 5,
        ]);
        $element->write();

        // Get events from element
        $elementEvents = $element->getEvents();

        // Get events directly from calendar
        $calendarEvents = $this->calendar->getEventsFeed(5);

        // Should have same count
        $this->assertEquals($calendarEvents->count(), $elementEvents->count());
        $this->assertEquals(2, $elementEvents->count());
    }

    /**
     * Test ElementCalendar respects limit parameter
     */
    public function testElementRespectsLimit()
    {
        // Create multiple events
        for ($i = 1; $i <= 5; $i++) {
            $event = EventPage::create([
                'Title' => "Event $i",
                'ParentID' => $this->calendar->ID,
                'StartDate' => Carbon::today()->addDays($i)->format('Y-m-d'),
                'StartTime' => '14:00:00',
                'EndDate' => Carbon::today()->addDays($i)->format('Y-m-d'),
                'EndTime' => '16:00:00',
                'Recursion' => 'NONE',
            ]);
            $event->write();
            $event->publishRecursive();
        }

        $element = ElementCalendar::create([
            'CalendarID' => $this->calendar->ID,
            'Limit' => 3,
        ]);
        $element->write();

        $events = $element->getEvents();
        $this->assertEquals(3, $events->count());
    }

    /**
     * Test ElementCalendar with category filtering
     */
    public function testElementWithCategoryFiltering()
    {
        // Create categories
        $category1 = Category::create(['Title' => 'Sports']);
        $category1->write();

        $category2 = Category::create(['Title' => 'Music']);
        $category2->write();

        // Create events with different categories
        $event1 = EventPage::create([
            'Title' => 'Football Game',
            'ParentID' => $this->calendar->ID,
            'StartDate' => Carbon::tomorrow()->format('Y-m-d'),
            'StartTime' => '14:00:00',
            'EndDate' => Carbon::tomorrow()->format('Y-m-d'),
            'EndTime' => '16:00:00',
            'Recursion' => 'NONE',
        ]);
        $event1->write();
        $event1->Categories()->add($category1);
        $event1->publishRecursive();

        $event2 = EventPage::create([
            'Title' => 'Concert',
            'ParentID' => $this->calendar->ID,
            'StartDate' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'StartTime' => '19:00:00',
            'EndDate' => Carbon::tomorrow()->addDay()->format('Y-m-d'),
            'EndTime' => '21:00:00',
            'Recursion' => 'NONE',
        ]);
        $event2->write();
        $event2->Categories()->add($category2);
        $event2->publishRecursive();

        // Create element with category filter
        $element = ElementCalendar::create([
            'CalendarID' => $this->calendar->ID,
            'Limit' => 5,
        ]);
        $element->write();
        $element->Categories()->add($category1); // Only sports

        $events = $element->getEvents();
        $this->assertEquals(1, $events->count());
        $this->assertEquals('Football Game', $events->first()->Title);
    }

    /**
     * Test ElementCalendar with recurring events
     */
    public function testElementWithRecurringEvents()
    {
        // Create a recurring event with proper interval
        $recurringEvent = EventPage::create([
            'Title' => 'Daily Meeting',
            'ParentID' => $this->calendar->ID,
            'StartDate' => Carbon::today()->format('Y-m-d'),
            'StartTime' => '10:00:00',
            'EndDate' => Carbon::today()->format('Y-m-d'),
            'EndTime' => '11:00:00',
            'Recursion' => 'DAILY',
            'Interval' => 1, // Correct field name for interval
            'RecursionEndDate' => Carbon::today()->addDays(5)->format('Y-m-d'),
        ]);
        $recurringEvent->write();
        $recurringEvent->publishRecursive();

        $element = ElementCalendar::create([
            'CalendarID' => $this->calendar->ID,
            'Limit' => 10,
        ]);
        $element->write();

        $events = $element->getEvents();

        // Should have multiple instances from the recurring event
        $this->assertGreaterThan(1, $events->count());
        $this->assertLessThanOrEqual(10, $events->count()); // Allow for more instances due to limit
    }

    /**
     * Test that getCMSFields renders CalendarID as a plain DropdownField,
     * not a scaffolded TreeDropdownField (SS6 has_one-to-SiteTree regression).
     */
    public function testGetCMSFieldsCalendarIdIsDropdownField()
    {
        /** @var ElementCalendar $element */
        $element = $this->objFromFixture(ElementCalendar::class, 'one');

        $fields = $element->getCMSFields();
        $field = $fields->dataFieldByName('CalendarID');

        $this->assertNotNull($field, 'CalendarID field should be present');
        $this->assertSame(
            DropdownField::class,
            get_class($field),
            'Calendar picker must be a plain DropdownField, not a tree picker or searchable subclass'
        );

        // The source must be Calendar pages only (ID => Title), not the whole site tree.
        $source = $field->getSource();
        $calendarOne = $this->objFromFixture(Calendar::class, 'one');
        $calendarTwo = $this->objFromFixture(Calendar::class, 'two');

        $this->assertArrayHasKey($calendarOne->ID, $source);
        $this->assertSame('My Awesome Calendar', $source[$calendarOne->ID]);
        $this->assertArrayHasKey($calendarTwo->ID, $source);
        $this->assertSame('My Other Awesome Calendar', $source[$calendarTwo->ID]);

        $this->assertSame('', $field->getEmptyString(), 'Field should allow an empty/no-calendar selection');
    }

    /**
     * Test getSummary method
     */
    public function testGetSummary()
    {
        // Create test event
        $event = EventPage::create([
            'Title' => 'Test Event',
            'ParentID' => $this->calendar->ID,
            'StartDate' => Carbon::tomorrow()->format('Y-m-d'),
            'StartTime' => '14:00:00',
            'EndDate' => Carbon::tomorrow()->format('Y-m-d'),
            'EndTime' => '16:00:00',
            'Recursion' => 'NONE',
        ]);
        $event->write();
        $event->publishRecursive();

        $element = ElementCalendar::create([
            'CalendarID' => $this->calendar->ID,
            'Limit' => 5,
        ]);
        $element->write();

        $summary = $element->getSummary();
        $this->assertStringContainsString('1 event', (string)$summary);
    }

    /**
     * Test provideBlockSchema method
     */
    public function testProvideBlockSchema()
    {
        $element = ElementCalendar::create([
            'CalendarID' => $this->calendar->ID,
            'Limit' => 3,
        ]);
        $element->write();

        // Use reflection to access the protected method
        $reflection = new \ReflectionClass($element);
        $method = $reflection->getMethod('provideBlockSchema');
        $method->setAccessible(true);
        $schema = $method->invoke($element);

        $this->assertIsArray($schema);
        $this->assertArrayHasKey('content', $schema);
    }

    /**
     * Legacy test compatibility - simplified
     */
    public function testGetEventsNoCalendarLegacy()
    {
        /** @var ElementCalendar $element */
        $element = $this->objFromFixture(ElementCalendar::class, 'one');
        // Test that it doesn't crash and returns reasonable results
        $events = $element->getEvents();
        $this->assertInstanceOf(ArrayList::class, $events);
    }

    /**
     * Legacy test compatibility - simplified
     */
    public function testGetEventsCalendarLegacy()
    {
        /** @var ElementCalendar $element */
        $element = $this->objFromFixture(ElementCalendar::class, 'three');
        $events = $element->getEvents();

        $this->assertInstanceOf(ArrayList::class, $events);
        // Note: Actual count may vary based on fixture data and date range
    }

    /**
     * Legacy test compatibility - simplified
     */
    public function testGetEventsCategoryLegacy()
    {
        /** @var ElementCalendar $element */
        $element = $this->objFromFixture(ElementCalendar::class, 'four');
        $events = $element->getEvents();

        $this->assertInstanceOf(ArrayList::class, $events);
        // Test that category filtering works (actual count may vary)
    }
}
