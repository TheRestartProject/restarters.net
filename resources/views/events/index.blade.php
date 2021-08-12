@extends('layouts.app')

@section('title')
    Events
@endsection

@section('content')

<section class="events events-page">
  <div class="container">

      @if (\Session::has('success'))
          <div class="alert alert-success">
              {!! \Session::get('success') !!}
          </div>
      @endif
      @if (\Session::has('warning'))
          <div class="alert alert-warning">
              {!! \Session::get('warning') !!}
          </div>
      @endif

      @if( is_null($group) )
      <div class="row mb-30">
          <div class="col-12 col-md-12">
              <div class="d-flex align-items-center">
                  <h1 class="mb-0 mr-30">
                      @lang('events.events')
                  </h1>

                  <div class="mr-auto d-none d-md-block">
                      @include('svgs.fixometer.events-doodle')
                  </div>

                  @if( App\Helpers\Fixometer::userCanCreateEvents(Auth::user()) )
                      <a href="/party/create" class="btn btn-primary ml-auto">
                          <span class="d-none d-lg-block">@lang('events.add_event')</span>
                          <span class="d-block d-lg-none">@lang('events.create_new_event_mobile')</span>
                      </a>
                  @endif
              </div>
          </div>
      </div>
      @endif

    {{-- Events List --}}
    <div class="row justify-content-center">
      <div class="col-lg-12">
        {{-- Events to Moderate (Admin Only) --}}
        @if ( App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator') && is_null($group) )
            <section class="table-section" id="events-1">
          <header>
            <h2><svg width="20" height="20" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M7.5 1.58a5.941 5.941 0 0 1 5.939 5.938A5.942 5.942 0 0 1 7.5 13.457a5.942 5.942 0 0 1-5.939-5.939A5.941 5.941 0 0 1 7.5 1.58zm0 3.04a2.899 2.899 0 1 1-2.898 2.899A2.9 2.9 0 0 1 7.5 4.62z"/><ellipse cx="6.472" cy=".217" rx=".274" ry=".217"/><ellipse cx="8.528" cy=".217" rx=".274" ry=".217"/><path d="M6.472 0h2.056v1.394H6.472z"/><path d="M8.802.217H6.198l-.274 1.562h3.152L8.802.217z"/><ellipse cx="8.528" cy="14.783" rx=".274" ry=".217"/><ellipse cx="6.472" cy="14.783" rx=".274" ry=".217"/><path d="M6.472 13.606h2.056V15H6.472z"/><path d="M6.198 14.783h2.604l.274-1.562H5.924l.274 1.562zM1.47 2.923c.107-.106.262-.125.347-.04.084.085.066.24-.041.347-.107.107-.262.125-.346.04-.085-.084-.067-.24.04-.347zM2.923 1.47c.107-.107.263-.125.347-.04.085.084.067.239-.04.346-.107.107-.262.125-.347.041-.085-.085-.066-.24.04-.347z"/><path d="M2.923 1.47L1.47 2.923l.986.986 1.453-1.453-.986-.986z"/><path d="M3.27 1.43L1.43 3.27l.91 1.299L4.569 2.34 3.27 1.43zm10.26 10.647c-.107.106-.262.125-.347.04-.084-.085-.066-.24.041-.347.107-.107.262-.125.346-.04.085.084.067.24-.04.347zm-1.453 1.453c-.107.107-.263.125-.347.04-.085-.084-.067-.239.04-.346.107-.107.262-.125.347-.041.085.085.066.24-.04.347z"/><path d="M12.077 13.53l1.453-1.453-.986-.986-1.453 1.453.986.986z"/><path d="M11.73 13.57l1.84-1.84-.91-1.299-2.229 2.229 1.299.91zM0 8.528c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274zm0-2.056c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274z"/><path d="M0 6.472v2.056h1.394V6.472H0z"/><path d="M.217 6.198v2.604l1.562.274V5.924l-1.562.274zM15 6.472c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274zm0 2.056c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274z"/><path d="M15 8.528V6.472h-1.394v2.056H15z"/><path d="M14.783 8.802V6.198l-1.562-.274v3.152l1.562-.274zM2.923 13.53c-.106-.107-.125-.262-.04-.347.085-.084.24-.066.347.041.107.107.125.262.04.346-.084.085-.24.067-.347-.04zM1.47 12.077c-.107-.107-.125-.263-.04-.347.084-.085.239-.067.346.04.107.107.125.262.041.347-.085.085-.24.066-.347-.04z"/><path d="M1.47 12.077l1.453 1.453.986-.986-1.453-1.453-.986.986z"/><path d="M1.43 11.73l1.84 1.84 1.299-.91-2.229-2.229-.91 1.299zM12.077 1.47c.106.107.125.262.04.347-.085.084-.24.066-.347-.041-.107-.107-.125-.262-.04-.346.084-.085.24-.067.347.04zm1.453 1.453c.107.107.125.263.04.347-.084.085-.239.067-.346-.04-.107-.107-.125-.262-.041-.347.085-.085.24-.066.347.04z"/><path d="M13.53 2.923L12.077 1.47l-.986.986 1.453 1.453.986-.986z"/><path d="M13.57 3.27l-1.84-1.84-1.299.91 2.229 2.229.91-1.299z"/></g></svg> @lang('events.events_title_admin')</h2>
          </header>

            <div class="table-responsive">
              <table class="table table-events table-striped table-layout-fixed" role="table">
                @include('events.tables.headers.head-events-admin-only', ['hide_invite' => true])
                <tbody>
                    @if( ! $moderate_events->isEmpty() )
                      @foreach ($moderate_events as $event)
                        @include('partials.tables.row-events', ['show_invites_count' => false])
                      @endforeach
                    @else
                      <tr>
                        <td colspan="7" align="center" class="p-3">@lang('events.moderation_none')</td>
                      </tr>
                    @endif
                </tbody>
              </table>
            </div>
          </section>
        @endif
        {{-- END Events to Moderate (Admin Only) --}}

      <?php

      $can_edit_group = Auth::user() && $group && (App\Helpers\Fixometer::hasRole( Auth::user(), 'Administrator') || $isCoordinatorForGroup || $is_host_of_group);
      $showCalendar = Auth::check() && (!$group || ($group && $group->isVolunteer()) || App\Helpers\Fixometer::hasRole( Auth::user(), 'Administrator'));
      $calendar_copy_url = '';
      $calendar_edit_url = '';

      if ($showCalendar) {
          if ($group) {
              $calendar_copy_url = url("/calendar/group/{$group->idgroups}");
              $calendar_edit_url = url("/profile/edit/" . Auth::user()->id);
          } else {
              $calendar_copy_url = url("/calendar/user/" . Auth::user()->calendar_hash);
              $calendar_edit_url = url("/profile/edit/" . Auth::user()->id . "#list-calendar-links");
          }
      }

      ?>

    <div class="vue-placeholder vue-placeholder-large">
        <div class="vue-placeholder-content">@lang('partials.loading')...</div>
    </div>
      @if( is_null($group) )
      <div class="vue">
        <GroupEvents
            heading-level="h2"
            heading-sub-level="h3"
            :initial-events="{{ json_encode($expanded_events, JSON_INVALID_UTF8_IGNORE) }}"
            :add-group-name="true"
            calendar-copy-url="{{ $calendar_copy_url }}"
            calendar-edit-url="{{ $calendar_edit_url }}"
            :add-button="false"
            :canedit="{{ $can_edit_group ? 'true' : 'false' }}"
            add-group-name
            show-other
        />
      </div>
      @else
      <div class="vue">
        <GroupEventsPage
          csrf="{{ csrf_token() }}"
          :idgroups="{{ $group ? $group->idgroups : 'null' }}"
          :events="{{ json_encode($expanded_events, JSON_INVALID_UTF8_IGNORE) }}"
          calendar-copy-url="{{ $showCalendar ? url("/calendar/group/{$group->idgroups}") : '' }}"
          calendar-edit-url="{{ $calendar_edit_url }}"
          :initial-group="{{ json_encode($group, JSON_INVALID_UTF8_IGNORE) }}"
          :canedit="{{ $can_edit_group ? 'true' : 'false' }}"
        />
      </div>
      @endif
      </div>
    {{-- END Events List --}}



  </div>
</section>

@endsection
