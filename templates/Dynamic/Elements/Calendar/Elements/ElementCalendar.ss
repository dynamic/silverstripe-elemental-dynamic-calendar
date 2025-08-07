<% if $Title && $ShowTitle %>
  <h2 class="element__title">$Title</h2>
<% end_if %>

<% if $Content %>
  <div class="element__content">$Content</div>
<% end_if %>

<% if $Calendar %>
  <div class="element-calendar-container"
       data-events-url="$Calendar.Link/events"
       data-calendar-id="$CalendarID"
       data-event-limit="$Limit"
       data-categories="<% if $Categories %>$Categories.column('ID').implode(',')<% end_if %>"
       <% if $CustomStyles %>data-button-styles="$CustomStyles"<% end_if %>>

    <!-- Compact FullCalendar with list, dayGrid week, and month views -->
    <div id="element-calendar-$ID" class="element-fullcalendar element-fullcalendar--compact"
         data-initial-view="listWeek"
         data-height="400"
         data-header-toolbar='{"left":"prev,next today","center":"title","right":"listWeek,dayGridWeek,dayGridMonth"}'>
      <div class="calendar-loading text-center py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Loading calendar...</span>
        </div>
      </div>
    </div>

    <% if $Calendar %>
      <div class="element-calendar-actions mt-3">
        <a href="$Calendar.Link" class="btn <% if $CustomStyles %>es-element__button<% else %>btn-primary<% end_if %>" <% if $CustomStyles %>style="$CustomStyles"<% end_if %> title="View full calendar">
          <i class="bi bi-calendar3"></i> View full calendar
        </a>
      </div>
    <% end_if %>
  </div>
<% else %>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    <strong>No calendar selected</strong> - Please select a calendar in the element settings.
  </div>
<% end_if %>

<%-- Include the calendar module assets (CSS and JS) --%>
<% require css("dynamic/silverstripe-calendar:client/dist/css/calendar.bundle.css") %>
<% require javascript("dynamic/silverstripe-calendar:client/dist/js/vendors.bundle.js") %>
<% require javascript("dynamic/silverstripe-calendar:client/dist/js/calendar.bundle.js") %>

<%-- Include our ElementCalendar handler --%>
<% require javascript("dynamic/silverstripe-elemental-dynamic-calendar:client/element-calendar.js") %>
