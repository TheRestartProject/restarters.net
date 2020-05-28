@extends('layouts.app')
@section('content')
<section class="events events-page">

    <div class="container-fluid">
        <div class="row">
            <div class="col">
                <div class="d-flex justify-content-between align-content-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{{ route('dashboard') }}}">FIXOMETER</a></li>
                            <li class="breadcrumb-item"><a href="{{{ route('events') }}}">Events</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Upcoming Events</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>

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

        <div class="row">
            <div class="col-lg-3">

                <form id="filter-result" action="{{ route('all-upcoming-events') }}" method="GET">

                    <div class="edit-panel edit-panel__side">

                        <div class="form-group">
                            <label for="from_date">@lang('events.upcoming_search_from'):</label>
                            <input type="date" name="from-date" id="from_date" class="field form-control" @if(isset($fromDate) && !empty($fromDate)) value="{{$fromDate}}" @endif>
                        </div>

                        <div class="form-group">
                            <label for="to_date">@lang('events.upcoming_search_to'):</label>
                            <input type="date" name="to-date" id="to_date" class="field form-control" @if(isset($toDate) && !empty($toDate)) value="{{$toDate}}" @endif>
                        </div>

                        <div class="form-group">
                            <label class="" style="font-weight:bold" for="online">
                                @lang('events.online_event_search'):
                                <input type="checkbox" id="online" style="margin: 0 0 0 5px; position: relative; top: 2px;" name="online" value="1" {{ $online ? 'checked' : '' }} />
                            </label>
                        </div>
                        <button class="btn btn-primary btn-filter" type="submit">Search</button>
                    </div>
                </form>

            </div>

            <div class="col-lg-9 mt-4 mt-lg-0">
                <header>
                    <h2>Upcoming events</h2>
                </header>

                @if ($hasSearched)
                <p>@lang('events.upcoming_search_match', ['count' => $upcoming_events_count])</p>
                    @if ($online)
                    <p>
                        Looking for online events?  Also see our <a href="{{{ env('DISCOURSE_URL') }}}/session/sso?return_path={{{ env('DISCOURSE_URL') }}}/c/events/">events listings on Talk</a> for other types of online events.
                    </p>
                    @endif
                @else
                <p>@lang('events.upcoming_search_count', ['count' => $upcoming_events_count])</p>
                @endif

                <div class="table-responsive">

                    <table class="table table-events table-striped" role="table">

                        @include('events.tables.head-events-upcoming')

                        <tbody>
                            @if( !$upcoming_events->isEmpty() )
                            @foreach ($upcoming_events as $event)

                            @include('events.tables.row-events-upcoming', ['invite' => true])

                            @endforeach
                            @else
                            <tr>
                                <td colspan="13" align="center" class="p-3">No events.</td>
                            </tr>
                            @endif
                        </tbody>

                    </table>

                </div>

                <div class="d-flex justify-content-center">
                    <nav aria-label="Page navigation example">
                        {!! $upcoming_events->links() !!}
                    </nav>
                </div>

            </div>
        </div>

    </div>
</section>
@endsection
