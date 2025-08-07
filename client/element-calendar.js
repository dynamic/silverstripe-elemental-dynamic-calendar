// ElementCalendar.js - FullCalendar integration with all view modes
// This extends the CalendarView from dynamic/silverstripe-calendar

document.addEventListener('DOMContentLoaded', function() {
  // Check if CalendarView is available from the main calendar module
  if (typeof window.CalendarView === 'undefined') {
    console.warn('ElementCalendar: CalendarView not found. Make sure dynamic/silverstripe-calendar assets are loaded.');
    return;
  }

  // Initialize all element calendar instances
  const elementCalendars = document.querySelectorAll('.element-fullcalendar');

  elementCalendars.forEach(element => {
    if (element.dataset.calendarInitialized) {
      return; // Already initialized
    }

    const container = element.closest('.element-calendar-container');
    if (!container) {
      console.warn('ElementCalendar: Container not found for element', element);
      return;
    }

    // Get configuration from data attributes
    const config = {
      eventsUrl: container.dataset.eventsUrl,
      calendarId: container.dataset.calendarId,
      eventLimit: parseInt(container.dataset.eventLimit) || 3,
      categories: container.dataset.categories ? container.dataset.categories.split(',').filter(Boolean) : []
    };

    // Get FullCalendar specific options from element data attributes
    const calendarOptions = {
      initialView: element.dataset.initialView || 'dayGridMonth',
      height: parseInt(element.dataset.height) || 600,
      headerToolbar: element.dataset.headerToolbar ? JSON.parse(element.dataset.headerToolbar) : {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
      },
      // Enable all the FullCalendar views
      views: {
        dayGridMonth: { buttonText: 'Month' },
        timeGridWeek: { buttonText: 'Week' },
        timeGridDay: { buttonText: 'Day' },
        listWeek: { buttonText: 'List' }
      },
      // Make it responsive
      aspectRatio: 1.8,
      // Event display options
      dayMaxEvents: 3,
      moreLinkClick: 'popover',
      // List view formatting
      listDayFormat: { weekday: 'long', month: 'short', day: 'numeric' },
      noEventsContent: 'No events scheduled'
    };

    try {
      // Remove loading spinner
      const loading = element.querySelector('.calendar-loading');
      if (loading) {
        loading.remove();
      }

      // Initialize using the existing CalendarView class
      const calendarView = new window.CalendarView(element, {
        ...calendarOptions,
        // Override the events function to use our element-specific config
        events: function(info, successCallback, failureCallback) {
          fetchElementEvents(config, info, successCallback, failureCallback);
        }
      });

      // Mark as initialized
      element.dataset.calendarInitialized = 'true';

      console.log(`ElementCalendar initialized with FullCalendar views`);

    } catch (error) {
      console.error('ElementCalendar: Failed to initialize calendar:', error);
    }
  });
});

// Function to fetch events for element calendars
function fetchElementEvents(config, info, successCallback, failureCallback) {
  if (!config.eventsUrl) {
    console.warn('ElementCalendar: No events URL provided');
    successCallback([]);
    return;
  }

  // Build URL with parameters
  const params = new URLSearchParams({
    start: info.startStr,
    end: info.endStr,
    format: 'json'
  });

  // Add category filtering
  if (config.categories.length > 0) {
    params.append('categories', config.categories.join(','));
  }

  const url = `${config.eventsUrl}?${params.toString()}`;

  fetch(url, {
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(response => {
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${response.statusText}`);
    }
    return response.json();
  })
  .then(events => {
    console.log(`ElementCalendar: Loaded ${events.length} events`);
    successCallback(events);
  })
  .catch(error => {
    console.error('ElementCalendar: Failed to fetch events:', error);
    failureCallback(error);
  });
}
