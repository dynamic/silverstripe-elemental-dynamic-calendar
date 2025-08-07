/**
 * ElementCalendar Multiple Instance Handler
 *
 * This file extends the existing Dynamic Calendar infrastructure to support
 * multiple ElementCalendar instances on the same page. It leverages the
 * existing FullCalendar v6 setup from the calendar module rather than
 * duplicating the complex bundling.
 */

// ElementCalendar class that handles individual calendar instances
class ElementCalendar {
  constructor(element, options = {}) {
    this.element = element;
    this.container = element.closest('.element-calendar-container');
    this.options = options;

    if (!this.container) {
      console.error('ElementCalendar: Container not found for element', element);
      return;
    }

    // Check if FullCalendar is available (from the calendar module)
    if (typeof FullCalendar === 'undefined') {
      console.error('ElementCalendar: FullCalendar not loaded. Make sure calendar module assets are included.');
      return;
    }

    this.init();
  }

  init() {
    console.log('Initializing ElementCalendar:', this.element.id);

    // Get configuration from data attributes
    const config = this.getConfigFromDataAttributes();

    // Remove loading spinner
    const loading = this.element.querySelector('.calendar-loading');
    if (loading) {
      loading.remove();
    }

    // Initialize FullCalendar using the global FullCalendar object
    this.initFullCalendar(config);
  }

  getConfigFromDataAttributes() {
    const container = this.container;
    const element = this.element;

    return {
      displayMode: container.dataset.displayMode || 'list',
      eventsUrl: container.dataset.eventsUrl,
      calendarId: container.dataset.calendarId,
      eventLimit: parseInt(container.dataset.eventLimit) || 3,
      categories: container.dataset.categories ? container.dataset.categories.split(',').filter(cat => cat.trim()) : [],
      initialView: element.dataset.initialView || 'listWeek',
      height: parseInt(element.dataset.height) || 300,
      headerToolbar: element.dataset.headerToolbar ? JSON.parse(element.dataset.headerToolbar) : {},
      footerToolbar: element.dataset.footerToolbar ? JSON.parse(element.dataset.footerToolbar) : null,
      dayMaxEvents: parseInt(element.dataset.dayMaxEvents) || false,
      moreLinkClick: element.dataset.moreLinkClick || 'popover',
      listDayFormat: element.dataset.listDayFormat ? JSON.parse(element.dataset.listDayFormat) : undefined,
      noEventsText: element.dataset.noEventsText || 'No events to display'
    };
  }

  initFullCalendar(config) {
    // Base FullCalendar configuration
    const calendarConfig = {
      initialView: config.initialView,
      height: config.height,
      headerToolbar: config.headerToolbar,
      dayMaxEvents: config.dayMaxEvents,
      moreLinkClick: config.moreLinkClick,
      eventDisplay: 'block',
      displayEventTime: true,
      eventTimeFormat: {
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short'
      },
      events: (info, successCallback, failureCallback) => {
        this.fetchEvents(info, successCallback, failureCallback, config);
      },
      eventClick: (info) => {
        this.handleEventClick(info);
      },
      noEventsContent: config.noEventsText,
      // Responsive behavior
      aspectRatio: config.displayMode === 'mini-calendar' ? 1.2 : 1.35,
    };

    // Add footer toolbar if specified
    if (config.footerToolbar) {
      calendarConfig.footerToolbar = config.footerToolbar;
    }

    // List view specific configuration
    if (config.initialView.includes('list')) {
      if (config.listDayFormat) {
        calendarConfig.listDayFormat = config.listDayFormat;
      }
    }

    // Initialize the calendar using the global FullCalendar object
    this.calendar = new FullCalendar.Calendar(this.element, calendarConfig);
    this.calendar.render();

    console.log(`ElementCalendar initialized: ${config.displayMode} mode on element ${this.element.id}`);
  }

  fetchEvents(info, successCallback, failureCallback, config) {
    if (!config.eventsUrl) {
      console.warn('ElementCalendar: No events URL provided for', this.element.id);
      successCallback([]);
      return;
    }

    // Build URL with parameters
    const params = new URLSearchParams({
      start: info.startStr,
      end: info.endStr,
      format: 'json'
    });

    // Add limit for non-full calendar views
    if (config.displayMode !== 'full-calendar' && config.eventLimit) {
      params.append('limit', config.eventLimit);
    }

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
      console.log(`ElementCalendar ${this.element.id}: Loaded ${events.length} events`);
      successCallback(events);
    })
    .catch(error => {
      console.error(`ElementCalendar ${this.element.id}: Failed to fetch events:`, error);
      failureCallback(error);
    });
  }

  handleEventClick(info) {
    // Prevent default link behavior
    info.jsEvent.preventDefault();

    // Get event data
    const event = info.event;

    // If event has a URL, open it
    if (event.url) {
      window.open(event.url, '_blank');
    }
  }

  destroy() {
    if (this.calendar) {
      this.calendar.destroy();
      this.calendar = null;
    }
  }

  refetchEvents() {
    if (this.calendar) {
      this.calendar.refetchEvents();
    }
  }
}

// Auto-initialize function that handles multiple calendar elements
function initializeElementCalendars() {
  console.log('Scanning for ElementCalendar instances...');

  // Find all calendar elements that haven't been initialized yet
  const calendarElements = document.querySelectorAll('.element-fullcalendar:not([data-calendar-initialized])');

  if (calendarElements.length === 0) {
    console.log('No ElementCalendar instances found to initialize');
    return;
  }

  console.log(`Found ${calendarElements.length} ElementCalendar instance(s) to initialize`);

  calendarElements.forEach(element => {
    try {
      new ElementCalendar(element);
      element.setAttribute('data-calendar-initialized', 'true');
    } catch (error) {
      console.error('Failed to initialize ElementCalendar:', error, element);
    }
  });
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initializeElementCalendars);
} else {
  // DOM is already ready
  initializeElementCalendars();
}

// Also initialize if called manually (for dynamic content)
window.initializeElementCalendars = initializeElementCalendars;

// Export for manual initialization
window.ElementCalendar = ElementCalendar;
