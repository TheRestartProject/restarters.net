@if ( is_null(Cookie::get("information-alert-dismissed-{$dismissable_id}")) && Auth::check() )

  <div class="alert alert-secondary information-alert alert-dismissible fade show @isset($classes) @foreach ($classes as $class) {{ $class }} @endforeach @endisset" role="alert" id="{{ $dismissable_id }}">
    <div class="d-sm-flex flex-row justify-content-between align-items-center">
      <div class="action-text-left float-left d-flex flex-row">
        <span class="icon my-auto">@include('partials.svg-icons.calendar-icon-lg')</span>
        <p class="action-text mb-0">{!! $html_text !!}</p>
      </div>

      <div class="float-right">
        @php( $user = Auth::user() )
        <button type="button" class="close set-dismissed-cookie float-none ml-2" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    </div>
  </div>

@endif
