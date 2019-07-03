@extends('layouts.app')

@section('title')
    Events
@endsection

@section('content')
<section class="events events-page">
  <div class="container-fluid">
    <div class="row">
      <div class="col">

        @include('partials.information-alert', [
          'html_text' => "<strong class='mb-2'>Did you know </strong> <br> You can now access all events using your personal calendar via an iCal feed? Find out more.",
          'dismissable_id' => 'party'
        ])

        <div class="d-flex justify-content-between align-content-center">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{{ route('dashboard') }}}">FIXOMETER</a></li>
              <li class="breadcrumb-item active" aria-current="page">@lang('events.events')</li>
            </ol>
          </nav>
          <div class="btn-group">
            @if( FixometerHelper::userCanCreateEvents(Auth::user()) && is_null($group) )
              <a href="/party/create" class="btn btn-primary btn-save">@lang('events.create_new_event')</a>
            @elseif( is_null($group) )
              <!--<a disabled title="Please create a group first" class="btn btn-primary btn-save disabled">@lang('events.create_new_event')</a>-->
            @endif
          </div>
        </div>
      </div>
    </div>

    {{-- Events List --}}
    <div class="row justify-content-center">
      <div class="col-lg-12">
        @if (\Session::has('success'))
        <div class="alert alert-success" role="alert">
          {!! \Session::get('success') !!}
        </div>
        @endif

        {{-- Events to Moderate (Admin Only) --}}
        @if ( FixometerHelper::hasRole(Auth::user(), 'Administrator') && is_null($group) )
          <header>
            <h1><svg width="20" height="20" viewBox="0 0 15 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M7.5 1.58a5.941 5.941 0 0 1 5.939 5.938A5.942 5.942 0 0 1 7.5 13.457a5.942 5.942 0 0 1-5.939-5.939A5.941 5.941 0 0 1 7.5 1.58zm0 3.04a2.899 2.899 0 1 1-2.898 2.899A2.9 2.9 0 0 1 7.5 4.62z"/><ellipse cx="6.472" cy=".217" rx=".274" ry=".217"/><ellipse cx="8.528" cy=".217" rx=".274" ry=".217"/><path d="M6.472 0h2.056v1.394H6.472z"/><path d="M8.802.217H6.198l-.274 1.562h3.152L8.802.217z"/><ellipse cx="8.528" cy="14.783" rx=".274" ry=".217"/><ellipse cx="6.472" cy="14.783" rx=".274" ry=".217"/><path d="M6.472 13.606h2.056V15H6.472z"/><path d="M6.198 14.783h2.604l.274-1.562H5.924l.274 1.562zM1.47 2.923c.107-.106.262-.125.347-.04.084.085.066.24-.041.347-.107.107-.262.125-.346.04-.085-.084-.067-.24.04-.347zM2.923 1.47c.107-.107.263-.125.347-.04.085.084.067.239-.04.346-.107.107-.262.125-.347.041-.085-.085-.066-.24.04-.347z"/><path d="M2.923 1.47L1.47 2.923l.986.986 1.453-1.453-.986-.986z"/><path d="M3.27 1.43L1.43 3.27l.91 1.299L4.569 2.34 3.27 1.43zm10.26 10.647c-.107.106-.262.125-.347.04-.084-.085-.066-.24.041-.347.107-.107.262-.125.346-.04.085.084.067.24-.04.347zm-1.453 1.453c-.107.107-.263.125-.347.04-.085-.084-.067-.239.04-.346.107-.107.262-.125.347-.041.085.085.066.24-.04.347z"/><path d="M12.077 13.53l1.453-1.453-.986-.986-1.453 1.453.986.986z"/><path d="M11.73 13.57l1.84-1.84-.91-1.299-2.229 2.229 1.299.91zM0 8.528c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274zm0-2.056c0-.151.097-.274.217-.274.119 0 .216.123.216.274 0 .151-.097.274-.216.274-.12 0-.217-.123-.217-.274z"/><path d="M0 6.472v2.056h1.394V6.472H0z"/><path d="M.217 6.198v2.604l1.562.274V5.924l-1.562.274zM15 6.472c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274zm0 2.056c0 .151-.097.274-.217.274-.119 0-.216-.123-.216-.274 0-.151.097-.274.216-.274.12 0 .217.123.217.274z"/><path d="M15 8.528V6.472h-1.394v2.056H15z"/><path d="M14.783 8.802V6.198l-1.562-.274v3.152l1.562-.274zM2.923 13.53c-.106-.107-.125-.262-.04-.347.085-.084.24-.066.347.041.107.107.125.262.04.346-.084.085-.24.067-.347-.04zM1.47 12.077c-.107-.107-.125-.263-.04-.347.084-.085.239-.067.346.04.107.107.125.262.041.347-.085.085-.24.066-.347-.04z"/><path d="M1.47 12.077l1.453 1.453.986-.986-1.453-1.453-.986.986z"/><path d="M1.43 11.73l1.84 1.84 1.299-.91-2.229-2.229-.91 1.299zM12.077 1.47c.106.107.125.262.04.347-.085.084-.24.066-.347-.041-.107-.107-.125-.262-.04-.346.084-.085.24-.067.347.04zm1.453 1.453c.107.107.125.263.04.347-.084.085-.239.067-.346-.04-.107-.107-.125-.262-.041-.347.085-.085.24-.066.347.04z"/><path d="M13.53 2.923L12.077 1.47l-.986.986 1.453 1.453.986-.986z"/><path d="M13.57 3.27l-1.84-1.84-1.299.91 2.229 2.229.91-1.299z"/></g></svg> @lang('events.events_title_admin')</h1>
          </header>

          <section class="table-section" id="events-1">
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
                        <td colspan="13" align="center" class="p-3">There are currently no events to moderate</td>
                      </tr>
                    @endif
                </tbody>
              </table>
            </div>
          </section>
        @endif
        {{-- END Events to Moderate (Admin Only) --}}


        {{-- Upcoming events for your Groups --}}
        <section class="table-section" id="events-2">
          <header>
            @if( !is_null($group) )
              <h2>Upcoming {{{ $group->name }}} events</h2>
            @else
              <h2>Upcoming events for your groups <sup>(<a href="{{{ route('all-upcoming-events') }}}">See all upcoming)</a></sup></h2>
            @endif

            <h3>For your groups
              @if ( Auth::check() )
                @php( $user = auth()->user() )
                @php( $copy_link = url("/calendar/user/{$user->calendar_hash}") )
                @php( $user_edit_link = url("/profile/edit/{$user->id}") )
                @include('partials.calendar-feed-button', [
                  'copy_link' => $copy_link,
                  'user_edit_link' => $user_edit_link,
                ])
              @endif
            </h3>
          </header>

          <div class="table-responsive">
            <table class="table table-events table-striped table-layout-fixed" role="table">
              @include('events.tables.headers.head-events-upcoming-only', ['hide_invite' => false])
              <tbody>
                @if( !$upcoming_events->isEmpty() )
                  @foreach ($upcoming_events as $event)
                    @include('partials.tables.row-events', ['show_invites_count' => true, 'EmissionRatio' => $EmissionRatio])
                  @endforeach
                @else
                  <tr>
                    <td colspan="13" align="center" class="p-3">There are currently no upcoming events for any of your groups<br><a href="{{{ route('groups') }}}">Find more groups</a></td>
                  </tr>
                @endif
              </tbody>
            </table>
          </div>
        </section>
        {{-- END Upcoming events for your Groups --}}


        {{-- Past events --}}
        <section class="table-section" id="events-3">
          <header>
            @if( !is_null($group) )
              <h2>Past {{{ $group->name }}} events</h2>
            @else
              <h2 class="mb-1">Past events <sup><a href="{{{ route('all-past-events') }}}">(See all past)</a></sup></h2>
              <p class="mb-2">These are past events from groups you a member of, and events that you RSVPed to.</p>
            @endif
          </header>

          <div class="table-responsive">
          <table class="table table-events table-striped table-layout-fixed" role="table">
              @include('partials.tables.head-events', ['hide_invite' => true])
              <tbody>
                @if( !$past_events->isEmpty() )
                  @foreach($past_events as $event)
                    @include('partials.tables.row-events', ['show_invites_count' => false, 'EmissionRatio' => $EmissionRatio])
                  @endforeach
                @else
                  <tr>
                    <td colspan="13" align="center" class="p-3">There are currently no past events for this group</td>
                  </tr>
                @endif
              </tbody>
          </table>
          </div>

          <div class="d-flex justify-content-center">
            <nav aria-label="Page navigation example">
              {!! $past_events->links() !!}
            </nav>
          </div>
        </section>
        {{-- END Past events --}}

      </div>
    </div>
    {{-- END Events List --}}



  </div>
</section>
@endsection
