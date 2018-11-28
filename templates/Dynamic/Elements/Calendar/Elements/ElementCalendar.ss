<% if $Title && $ShowTitle %><h2 class="element__title">$Title</h2><% end_if %>
<% if $Content %><div class="element__content">$Content</div><% end_if %>

<% if $Events %>
    <div class="row">
    <% loop $Events %>
        <div class="col-md-4">
            <h3>
                <a href="$Link" title="Go to the $Title.XML page">
                    <% if $MenuTitle %>
                        $MenuTitle
                    <% else %>
                        $Title
                    <% end_if %>
                </a>
            </h3>
            <% if $StartDate %><h4>$StartDate.Format("MMMM d,  Y")</h4><% end_if %>

            <% if $Abstract %>
                <p class="hidden-sm hidden-xs">$Abstract</p>
            <% else_if $Abstract %>
                <p class="hidden-sm hidden-xs">$Content.FirstParagraph</p>
            <% end_if %>

            <a href="$Link" title="Go to the $Title.XML page">Learn More</a>
        </div>
    <% end_loop %>
    </div>
    <p><a href="$Calendar.Link" class="btn btn-primary" title="View all events">View all events</a></p>
<% end_if %>

