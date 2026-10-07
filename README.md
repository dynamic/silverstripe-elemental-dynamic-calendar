# Silverstripe Elemental Dynamic Calendar

## Requirements

* dynamic/dynamic-calendar: ^3@dev
* dnadesign/silverstripe-elemental: ^4@dev

## Installation

`composer require dynamic/silverstripe-elemental-dynamic-calendar`

## Example usage

A block that displays upcoming events.

### Template Notes

The default templates are based off [Bootstrap 4](https://getbootstrap.com/) classes/styling.

## Getting more elements

See [Elemental modules by Dynamic](https://github.com/dynamic/silverstripe-elemental-blocks#included-blocks)

## Configuration

See [SilverStripe Elemental Configuration](https://github.com/dnadesign/silverstripe-elemental#configuration)

### Calendar block event window

`Dynamic\Elements\Calendar\Elements\ElementCalendar` bounds how far it asks
`dynamic/silverstripe-calendar` for events, so that rendering a block does not expand a
calendar's whole event corpus - and every recurring occurrence in it - just to list a few
upcoming events.

```yml
# any _config/*.yml in your project
Dynamic\Elements\Calendar\Elements\ElementCalendar:
  events_window_months: 6
  events_window_backfill_days: 1
```

* `events_window_months` (default `6`): how far ahead, in months, the feed is fetched.
  The window closes at the end of that month. Set it to `0` - or any non-positive value -
  to disable the bound and restore the unbounded fetch.
* `events_window_backfill_days` (default `1`): how many days before today the window
  reaches back, so an event that started yesterday and has not finished is still listed.
  Set it to `0` to start the window exactly today.

Both bounds are applied to `StartDate` only, because `Calendar::getEventsFeed()` filters on
`StartDate` only. That has one consequence worth knowing about:
`events_window_backfill_days` keeps a running event visible only if it *started* within
that many days before today. An event that started earlier and is still running - a
week-long festival on its fourth day - is left out. Raise
`events_window_backfill_days` to at least the longest event duration on the site to work
around it; a proper "started before today but not yet finished" overlap mode is tracked
upstream in [dynamic/silverstripe-calendar#267](https://github.com/dynamic/silverstripe-calendar/issues/267)
and this module will adopt it there.

Events whose end date falls before today are dropped from the window before the block's
`Limit` is applied, so a finished event inside the backfill period cannot take a display
slot away from an upcoming one. The comparison is by date, not by time of day: an event
that ends today stays listed until tomorrow.
