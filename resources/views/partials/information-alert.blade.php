@if ( is_null(Cookie::get("information-alert-dismissed-{$dismissable_id}")) && Auth::check() )

<div class="d-none d-md-block">
  <div class="alert alert-secondary information-alert alert-dismissible fade show" role="alert" id="{{ $dismissable_id }}">
    <div class="d-flex flex-row justify-content-between align-items-center">
      <div class="action-text-left float-left d-flex flex-row">
        <span class="my-auto">@include('partials.svg-icons.calendar-icon-lg')</span>
        <p class="action-text mb-0"><strong class="mb-2">Did you know </strong> <br> You can now access all events using your personal calendar via an iCal feed? Find out more.</p>
      </div>


      <div class="float-right">
        @php( $user = Auth::user() )
        <button type="button" name="button" class="btn btn-action btn-primary" data-copy-link="{{ url("/calendar/user/{$user->calendar_hash}") }}">Copy iCal link </button>
        <button type="button" class="close set-dismissed-cookie float-none ml-2" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
    </div>
  </div>
</div>

@endif
