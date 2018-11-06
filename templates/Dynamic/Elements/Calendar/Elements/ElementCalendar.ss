<% if $Title && $ShowTitle %><h2 class="element__title">$Title</h2><% end_if %>
<% if $Content %><div class="element__content">$Content</div><% end_if %>

<% if $Events %>
    <% loop $Events %>
        <li>
            <h4>
                <a href="$Link">
                    <% if $MenuTitle %>
                        $MenuTitle
                    <% else %>
                        $Title
                    <% end_if %>
                </a>
            </h4>
            <h5>$StartDate</h5>
            <% if $Abstract || $Content %><p class="hidden-sm hidden-xs"><% end_if %>
            <% if $Abstract %>$Abstract<% else_if $Content %>$Content.Summary<% end_if %>
            <% if $Abstract || $Content %></p><% end_if %>
            <a class="readMoreLink" href="$Link" title="Read more about &quot;{$Title}&quot;">Read more about&quot;{$Title}&quot;...</a>
        </li>
    <% end_loop %>
    <p><a href="$Calendar.Link" class="btn btn-primary" title="Go to the $Title page">View all events</a></p>
<% end_if %>

