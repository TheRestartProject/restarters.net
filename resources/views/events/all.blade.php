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

        <div class="row justify-content-center">
            <div class="col-lg-3">

                <form id="filter-result" action="{{ route('all-upcoming-events') }}" method="GET">

                    <button class="btn btn-primary btn-filter" type="submit">Search</button>
                    <div class="edit-panel edit-panel__side">

                        <div class="form-group">
                            <label for="from_date">@lang('devices.from_date'):</label>
                            <input type="date" name="from-date" id="from_date" class="field form-control" @if(isset($fromDate) && !empty($fromDate)) value="{{$fromDate}}" @endif>
                        </div>

                        <div class="form-group">
                            <label for="to_date">@lang('devices.to_date'):</label>
                            <input type="date" name="to-date" id="to_date" class="field form-control" @if(isset($toDate) && !empty($toDate)) value="{{$toDate}}" @endif>
                        </div>

                        <div class="form-group">
                            <label for="online">@lang('events.online_event_search'):</label>
                            <input type="checkbox" id="online" name="online" value="1" {{ $online ? 'checked' : '' }} />
                        </div>
                    </div>
                </form>

            </div>

            <div class="col-lg-9">
                <header>
                    <h2>Upcoming events</h2>
                </header>

                @if ($hasSearched)
                <p>There are {{ $upcoming_events_count }} upcoming events that meet your search criteria.</p>
                @else
                <p>There are {{ $upcoming_events_count }} upcoming events.</p>
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
