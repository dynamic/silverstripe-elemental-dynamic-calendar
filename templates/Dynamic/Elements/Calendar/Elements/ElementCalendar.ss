<% if $Title && $ShowTitle %><h2 class="element__title">$Title</h2><% end_if %>
<% if $Content %><div class="element__content">$Content</div><% end_if %>

<% if $Events %>
    <div class="events-list">
    <% loop $Events %>
        <% include Dynamic/Calendar/Includes/EventCompact %>
    <% end_loop %>
    </div>

    <% if $Calendar %>
        <div class="mt-3">
            <a href="$Calendar.Link" class="btn btn-primary" title="View all events">
                <i class="bi bi-calendar3"></i> View all events
            </a>
        </div>
    <% end_if %>
<% else %>
    <div class="alert alert-info">
        <i class="bi bi-calendar-x"></i>
        <strong>No upcoming events</strong> - Check back soon for new events!
    </div>
<% end_if %>

