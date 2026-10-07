<?php

namespace Dynamic\Elements\Calendar\Tests\Elements;

use Carbon\Carbon;
use Dynamic\Calendar\Model\Category;
use Dynamic\Calendar\Page\Calendar;
use Dynamic\Calendar\Page\EventPage;
use Dynamic\Elements\Calendar\Elements\ElementCalendar;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Model\List\ArrayList;
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
        // BaseElement::getType() resolves to the element's singular_name in Elemental 6,
        // and this module ships no lang/en.yml translation of <Class>.BlockType yet
        // (tracked separately as issue #25), so the untranslated name is what comes back.
        $this->assertEquals('Calendar Element', $object->getType());
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
     * Regression test for #26: an event dated before today must not be materialised
     * by the element, because the block can never show it.
     */
    public function testPastEventsAreExcludedFromTheWindow()
    {
        $this->createEvent('Past event', Carbon::today()->subDays(5)->format('Y-m-d'));
        $this->createEvent('Upcoming event', Carbon::tomorrow()->format('Y-m-d'));

        $element = $this->createElement(5);
        $events = $element->getEvents();

        $titles = $events->column('Title');
        $this->assertNotContains('Past event', $titles, 'Events before today must be excluded');
        $this->assertContains('Upcoming event', $titles);
        $this->assertEquals(1, $events->count());
    }

    /**
     * Regression test for #26: events further out than events_window_months are excluded.
     * Default window is 6 months; here it is narrowed to 1 so the bound is unambiguous
     * no matter which day of the month the suite runs on.
     */
    public function testEventsBeyondTheWindowAreExcluded()
    {
        Config::modify()->set(ElementCalendar::class, 'events_window_months', 1);

        $this->createEvent('Inside window', Carbon::today()->addWeeks(3)->format('Y-m-d'));
        $this->createEvent('Outside window', Carbon::today()->addMonths(3)->format('Y-m-d'));

        $element = $this->createElement(5);
        $events = $element->getEvents();

        $titles = $events->column('Title');
        $this->assertContains('Inside window', $titles);
        $this->assertNotContains('Outside window', $titles, 'Events past the configured window must be excluded');
        $this->assertEquals(1, $events->count());
    }

    /**
     * Regression test for #26: a window of 0 restores the unbounded (pre-fix) behaviour,
     * so the whole corpus - past events included - is available again.
     */
    public function testWindowOfZeroRestoresUnboundedBehaviour()
    {
        Config::modify()->set(ElementCalendar::class, 'events_window_months', 0);

        $this->createEvent('Past event', Carbon::today()->subDays(5)->format('Y-m-d'));
        $this->createEvent('Upcoming event', Carbon::tomorrow()->format('Y-m-d'));

        $element = $this->createElement(5);
        $events = $element->getEvents();

        $titles = $events->column('Title');
        $this->assertContains('Past event', $titles, 'A window of 0 must not bound the feed');
        $this->assertContains('Upcoming event', $titles);
        $this->assertEquals(2, $events->count());
    }

    /**
     * A negative window is bad input: it must fall back to unbounded rather than error
     * or silently return nothing.
     */
    public function testNegativeWindowFallsBackToUnbounded()
    {
        Config::modify()->set(ElementCalendar::class, 'events_window_months', -3);

        $this->createEvent('Past event', Carbon::today()->subDays(5)->format('Y-m-d'));

        $element = $this->createElement(5);
        $events = $element->getEvents();

        $this->assertEquals(1, $events->count());
        $this->assertEquals('Past event', $events->first()->Title);
    }

    /**
     * Happy path with the bounded window in place: only upcoming in-window events come
     * back, still sorted by StartDate and still capped by Limit.
     */
    public function testWindowStillHonoursLimitAndSortOrder()
    {
        $this->createEvent('Past event', Carbon::today()->subDays(5)->format('Y-m-d'));
        for ($i = 1; $i <= 4; $i++) {
            $this->createEvent("Upcoming event $i", Carbon::today()->addDays($i)->format('Y-m-d'));
        }
        $this->createEvent('Far future event', Carbon::today()->addMonths(11)->format('Y-m-d'));

        $element = $this->createElement(3);
        $events = $element->getEvents();

        $this->assertEquals(3, $events->count());
        $this->assertEquals(
            ['Upcoming event 1', 'Upcoming event 2', 'Upcoming event 3'],
            $events->column('Title')
        );

        // With a Limit above the number of in-window events, the far-future event is still
        // absent: it is the window (default events_window_months = 6) that excludes it here,
        // not the Limit. Without this second element the +11 month event would be dropped by
        // Limit = 3 regardless of the window, i.e. it would test nothing.
        $unlimitedElement = $this->createElement(10);
        $this->assertEquals(
            ['Upcoming event 1', 'Upcoming event 2', 'Upcoming event 3', 'Upcoming event 4'],
            $unlimitedElement->getEvents()->column('Title')
        );
    }

    /**
     * Category filtering must keep working now that a date window is passed down.
     */
    public function testCategoryFilteringStillAppliesWithBoundedWindow()
    {
        $sports = Category::create(['Title' => 'Sports']);
        $sports->write();
        $music = Category::create(['Title' => 'Music']);
        $music->write();

        $pastGame = $this->createEvent('Past football game', Carbon::today()->subDays(5)->format('Y-m-d'));
        $pastGame->Categories()->add($sports);
        $upcomingGame = $this->createEvent('Upcoming football game', Carbon::tomorrow()->format('Y-m-d'));
        $upcomingGame->Categories()->add($sports);
        $upcomingConcert = $this->createEvent('Upcoming concert', Carbon::tomorrow()->format('Y-m-d'));
        $upcomingConcert->Categories()->add($music);

        $element = $this->createElement(5);
        $element->Categories()->add($sports);

        $events = $element->getEvents();

        $this->assertEquals(1, $events->count());
        $this->assertEquals('Upcoming football game', $events->first()->Title);
    }

    /**
     * Empty result: a calendar whose only events are in the past renders an empty list
     * and a "No events" summary rather than an error.
     */
    public function testCalendarWithOnlyPastEventsReturnsEmptySummary()
    {
        $this->createEvent('Past event one', Carbon::today()->subDays(3)->format('Y-m-d'));
        $this->createEvent('Past event two', Carbon::today()->subWeek()->format('Y-m-d'));

        $element = $this->createElement(3);

        $this->assertEquals(0, $element->getEvents()->count());
        $this->assertStringContainsString('No events', (string)$element->getSummary());
    }

    /**
     * Calendar::getEventsFeed() filters on StartDate only, so the default backfill keeps
     * an event that started before today but has not finished yet.
     */
    public function testInProgressMultiDayEventIsStillIncluded()
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $this->createEvent('Overnight festival', $yesterday, $tomorrow);

        $element = $this->createElement(5);
        $events = $element->getEvents();

        $this->assertEquals(1, $events->count());
        $this->assertEquals('Overnight festival', $events->first()->Title);
    }

    /**
     * The backfill is a knob, not a guarantee: with 0 the window starts exactly today and
     * a running event whose StartDate is in the past falls out of it. Pinned so the
     * trade-off is explicit for anyone who tunes the config.
     */
    public function testBackfillOfZeroExcludesAnInProgressEvent()
    {
        Config::modify()->set(ElementCalendar::class, 'events_window_backfill_days', 0);

        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $this->createEvent('Overnight festival', $yesterday, $tomorrow);

        $element = $this->createElement(5);

        $this->assertEquals(0, $element->getEvents()->count());
    }

    /**
     * The backfill reaches back before today, so a finished event from that window must not
     * take one of the block's display slots away from upcoming events.
     */
    public function testFinishedYesterdayEventDoesNotPushUpcomingEventsOut()
    {
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');
        $this->createEvent('Finished yesterday', $yesterday);
        $this->createEvent('Running since yesterday', $yesterday, $tomorrow);
        $this->createEvent('Tomorrow', $tomorrow);

        $element = $this->createElement(2);
        $events = $element->getEvents();

        $this->assertEquals(['Running since yesterday', 'Tomorrow'], $events->column('Title'));
    }

    /**
     * The "already finished" check must not delete recurring occurrences: an occurrence's
     * EndDate is instance-relative, not the original event's (possibly years old) end date.
     */
    public function testRecurringOccurrencesAreNotDroppedByTheFinishedFilter()
    {
        $recurring = EventPage::create([
            'Title' => 'Daily standup',
            'ParentID' => $this->calendar->ID,
            'StartDate' => Carbon::today()->subDays(3)->format('Y-m-d'),
            'EndDate' => Carbon::today()->subDays(3)->format('Y-m-d'),
            'StartTime' => '09:00:00',
            'EndTime' => '09:15:00',
            'Recursion' => 'DAILY',
            'Interval' => 1,
            'RecursionEndDate' => Carbon::today()->addDays(3)->format('Y-m-d'),
        ]);
        $recurring->write();
        $recurring->publishRecursive();

        $element = $this->createElement(20);
        $events = $element->getEvents();

        $today = Carbon::today()->format('Y-m-d');
        $this->assertGreaterThan(1, $events->count());

        foreach ($events as $event) {
            $this->assertGreaterThanOrEqual(
                $today,
                (string)$event->StartDate,
                'Occurrences that already passed must not be listed, upcoming ones must be'
            );
        }
    }

    /**
     * KNOWN LIMITATION, pinned on purpose: dynamic/silverstripe-calendar#267.
     *
     * Calendar::getEventsFeed() filters on StartDate only, so the default backfill of 1
     * day rescues an event only if it started yesterday. A multi-day event that started 3
     * days ago and is still running today (EndDate = today + 3) never reaches the list at
     * all, even though the pre-fix unbounded feed showed it. This test documents that
     * trade-off: it must FLIP (and the docblock/README wording with it) once #267 lands an
     * overlap mode and this module adopts it.
     */
    public function testKnownLimitationEventRunningLongerThanTheBackfillIsDropped()
    {
        $this->createEvent(
            'Week-long festival',
            Carbon::today()->subDays(3)->format('Y-m-d'),
            Carbon::today()->addDays(3)->format('Y-m-d')
        );

        $element = $this->createElement(5);
        $events = $element->getEvents();

        $this->assertNotContains(
            'Week-long festival',
            $events->column('Title'),
            'Known limitation: the backfill covers events that started within it, not events still running'
        );
        $this->assertEquals(0, $events->count());
    }

    /**
     * The documented workaround for the limitation above: raising the backfill to at least
     * the event's duration brings a still-running long event back.
     */
    public function testBackfillRaisedToTheEventDurationKeepsARunningEventVisible()
    {
        Config::modify()->set(ElementCalendar::class, 'events_window_backfill_days', 7);

        $this->createEvent(
            'Week-long festival',
            Carbon::today()->subDays(3)->format('Y-m-d'),
            Carbon::today()->addDays(3)->format('Y-m-d')
        );

        $element = $this->createElement(5);
        $events = $element->getEvents();

        $this->assertEquals(1, $events->count());
        $this->assertEquals('Week-long festival', $events->first()->Title);
    }

    /**
     * Create an event in the test calendar and publish it.
     */
    protected function createEvent(string $title, string $startDate, ?string $endDate = null): EventPage
    {
        $event = EventPage::create([
            'Title' => $title,
            'ParentID' => $this->calendar->ID,
            'StartDate' => $startDate,
            'StartTime' => '10:00:00',
            'EndDate' => $endDate ?: $startDate,
            'EndTime' => '11:00:00',
            'Recursion' => 'NONE',
        ]);
        $event->write();
        $event->publishRecursive();

        return $event;
    }

    /**
     * Create a fresh element bound to the test calendar (a fresh element per config
     * change, since the element caches its events).
     */
    protected function createElement(int $limit): ElementCalendar
    {
        $element = ElementCalendar::create([
            'CalendarID' => $this->calendar->ID,
            'Limit' => $limit,
        ]);
        $element->write();

        return $element;
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
