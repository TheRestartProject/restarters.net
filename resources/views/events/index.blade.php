@extends('layouts.app')

@section('title')
    Events
@endsection

@section('content')

{{-- {{ dd(true) }} --}}

<section class="events events-page">
  <div class="container-fluid">
    <div class="row">
        <div class="col">


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
            @if( is_null($group) )

                @include('partials.information-alert', [
                    'html_text' => "<span class='badge badge-success'>NEW!</span> <strong class='mb-2'>Do you use Google / Outlook / Yahoo calendars?</strong> <br> You can now add all your upcoming events to your personal calendar with the <button disabled type='button' class='btn btn-normal-padding btn-sm btn-primary' data-original-title='' title=''><svg width='12' height='12' viewBox='0 0 50 50' version='1.1' xmlns='http://www.w3.org/2000/svg' xmlns:xlink='http://www.w3.org/1999/xlink' xml:space='preserve' xmlns:serif='http://www.serif.com/' style='fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;'>
                    <g transform='matrix(2.77778,0,0,2.77778,-2169.96,-58.3333)'>
                <path d='M797.801,39L782.57,39C782.195,39 781.871,38.859 781.597,38.576C781.322,38.293 781.185,37.958 781.185,37.572L781.185,25.286C781.185,24.899 781.322,24.564 781.597,24.281C781.871,23.999 782.195,23.857 782.57,23.857L783.955,23.857L783.955,22.786C783.955,22.295 784.124,21.874 784.463,21.525C784.802,21.175 785.21,21 785.686,21L786.378,21C786.854,21 787.261,21.175 787.6,21.525C787.939,21.874 788.109,22.295 788.109,22.786L788.109,23.857L792.262,23.857L792.262,22.786C792.262,22.295 792.432,21.874 792.771,21.525C793.11,21.175 793.517,21 793.993,21L794.686,21C795.162,21 795.569,21.175 795.908,21.525C796.247,21.874 796.417,22.295 796.417,22.786L796.417,23.857L797.801,23.857C798.176,23.857 798.501,23.999 798.775,24.281C799.048,24.564 799.185,24.899 799.185,25.286L799.185,37.572C799.185,37.958 799.048,38.293 798.774,38.576C798.501,38.859 798.176,39 797.801,39ZM797.685,33.927L794.685,33.927L794.685,36.472L797.685,36.472L797.685,33.927ZM789.685,33.927L786.685,33.927L786.685,36.472L789.685,36.472L789.685,33.927ZM785.685,33.927L782.685,33.927L782.685,36.472L785.685,36.472L785.685,33.927ZM793.685,33.927L790.685,33.927L790.685,36.472L793.685,36.472L793.685,33.927ZM797.685,30.383L794.685,30.383L794.685,32.927L797.685,32.927L797.685,30.383ZM789.685,30.383L786.685,30.383L786.685,32.927L789.685,32.927L789.685,30.383ZM785.685,30.383L782.685,30.383L782.685,32.927L785.685,32.927L785.685,30.383ZM793.685,30.383L790.685,30.383L790.685,32.927L793.685,32.927L793.685,30.383ZM797.685,26.838L794.685,26.838L794.685,29.383L797.685,29.383L797.685,26.838ZM789.685,26.838L786.685,26.838L786.685,29.383L789.685,29.383L789.685,26.838ZM785.685,26.838L782.685,26.838L782.685,29.383L785.685,29.383L785.685,26.838ZM793.685,26.838L790.685,26.838L790.685,29.383L793.685,29.383L793.685,26.838ZM786.378,22.429L785.686,22.429C785.592,22.429 785.511,22.464 785.442,22.535C785.374,22.605 785.339,22.689 785.339,22.786L785.339,25C785.339,25.097 785.374,25.18 785.442,25.251C785.511,25.322 785.592,25.357 785.686,25.357L786.378,25.357C786.472,25.357 786.553,25.322 786.621,25.251C786.69,25.18 786.724,25.097 786.724,25L786.724,22.786C786.724,22.689 786.69,22.605 786.621,22.535C786.553,22.464 786.472,22.429 786.378,22.429ZM794.686,22.429L793.993,22.429C793.899,22.429 793.818,22.464 793.75,22.535C793.681,22.605 793.647,22.689 793.647,22.786L793.647,25C793.647,25.097 793.681,25.18 793.75,25.251C793.818,25.322 793.9,25.357 793.993,25.357L794.686,25.357C794.779,25.357 794.861,25.322 794.929,25.251C794.997,25.18 795.032,25.097 795.032,25L795.032,22.786C795.032,22.689 794.998,22.605 794.929,22.535C794.861,22.464 794.779,22.429 794.686,22.429Z' style='fill:white;fill-rule:nonzero;'></path>
                </g>
            </svg>
            <div class='span-vertically-align-middle'>Add to calendar</div></button> button below.",
            'dismissable_id' => 'partycalendar'
            ])
            @endif

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
              <h2>Upcoming events for your groups
                  @if ( Auth::check() )
                      @php( $user = auth()->user() )
                      @php( $copy_link = url("/calendar/user/{$user->calendar_hash}") )
                      @php( $user_edit_link = url("/profile/edit/{$user->id}") )
                      @include('partials.calendar-feed-button', [
                                     'copy_link' => $copy_link,
                                     'user_edit_link' => $user_edit_link,
                                     'modal_title' => 'Access all events in your personal calendar',
                                     'modal_text' => 'Add all your upcoming events to your google/Outlook/Yahoo/Apple calendar with the link below.',
                                     ])
                  @endif
                  @if ( FixometerHelper::hasRole(Auth::user(), 'Administrator') && is_null($group) )
                      <sup>(<a href="{{{ route('all-upcoming-events') }}}">See all upcoming)</a></sup></h2>
                  @endif
            @endif

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
                  <h2>Upcoming events for your groups</h2>
                @endif
              </tbody>
            </table>
          </div>
        </section>
        {{-- END Upcoming events for your Groups --}}


        @if( is_null($group) )
        <section class="table-section upcoming_events_in_area" id="events-3">
            <header>
                <h2>Other events near you</h2>
            </header>
            <div class="table-responsive">
                <table class="table table-events table-striped" role="table">
                    @include('events.tables.headers.head-events-upcoming-only', ['hide_invite' => false])
                    <tbody>
                        @if ( is_null(auth()->user()->latitude) && is_null(auth()->user()->longitude) )
                            <tr>
                                <td colspan="13" align="center" class="p-3">Your location has not been set.<br><a href="{{{ route('edit-profile', ['id' => auth()->id()]) }}}">Click here to set your location.</a></td>
                            </tr>
                        @elseif( !$upcoming_events_in_area->isEmpty() )
                            @foreach($upcoming_events_in_area as $event)
                                @include('partials.tables.row-events', ['show_invites_count' => true, 'EmissionRatio' => $EmissionRatio])
                            @endforeach
                        @else
                            <tr>
                                <td colspan="13" align="center" class="p-3">There are no upcoming events near you - get in touch with your <a href="/group">local groups</a> to see if any are planned, or would you like to start or add a group? Have a look at our <a href="{{ env('DISCOURSE_URL' )}}/session/sso?return_path={{ env('DISCOURSE_URL') }}/t/2-how-to-run-a-repair-event/28" target="_blank" rel="noopener noreferrer">resources</a>.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>
        @endif


        {{-- Past events --}}
        <section class="table-section" id="events-4">
          <header>
            @if( !is_null($group) )
              <h2>Past {{{ $group->name }}} events</h2>
            @else
              <h2 class="mb-1">Past events <sup><a href="{{{ route('all-past-events') }}}">(See all past)</a></sup></h2>
              <p class="mb-2">These are past events from groups you are a member of, and events that you RSVPed to.</p>
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
