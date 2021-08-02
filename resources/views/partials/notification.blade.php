<div class="card {{ $notification->read_at ? 'status-is-read' : 'status-read' }} {{{ App\Helpers\Fixometer::notificationClasses($notification->type) }}}">
    <div class="card-body">
        <h5 class="card-title mb-1">
            @if (!empty($notification->data['title']))
                {{{ $notification->data['title'] }}}
            @endif

            @if (!empty($notification->data['url']))
                <a href="{{{ $notification->data['url'] }}}">{{{ $notification->data['name'] }}}</a>
            @else
                @if (!empty($notification->data['name']))
                    {{{ $notification->data['name'] }}}
                @endif
            @endif
        </h5>
        <time title="{{{ $notification->created_at->toDayDateTimeString() }}}">{{{ $notification->created_at->diffForHumans() }}}</time>
        <div class="d-flex flex-row justify-content-end mt-1">
            <a href="{{ route('markAsRead', ['id' => $notification->id]) }}" class="btn-marked">@lang('notifications.mark_as_read')</a>
            <span class="marked-as-read"><svg width="13px" height="9" viewBox="0 0 54 37" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Green tick icon</title><path d="M4.615 14.064a.969.969 0 0 0-1.334 0l-3 2.979a.868.868 0 0 0 0 1.279l18.334 18c.333.35.916.35 1.291 0l3.042-2.983a.869.869 0 0 0 0-1.28L4.615 14.064z" fill="#0394a6"/><path d="M53.365 4.584a.913.913 0 0 0 .041-1.287L50.365.272c-.334-.358-.959-.363-1.292-.013L15.99 32.109a.873.873 0 0 0 0 1.284l3 3.029a.97.97 0 0 0 1.333.012l33.042-31.85z" fill="#0394a6"/></svg> @lang('notifications.marked_as_read')</span>
        </div>
    </div>
</div>
