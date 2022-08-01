@extends('layouts.app')
@section('content')
<section>
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-12 mb-50">
                <div class="d-flex align-items-center">
                    <h1 class="mb-0 mr-30">
                        Profile & Preferences
                    </h1>

                    @if (Auth::id() == $user->id)
                        <a href="/profile" class="btn btn-primary ml-auto">@lang('profile.view_profile')</a>
                    @else
                        <a href="/profile/{{ $user->id }}" class="btn btn-primary ml-auto">@lang('profile.view_user_profile')</a>
                    @endif
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-4 offset-lg-sidebar">
                <div class="list-group" id="list-tab" role="tablist">
                    <a class="list-group-item list-group-item-action" id="list-profile-list"
                       href="{{ route('edit-profile', ['id' => $user->id]) }}#list-profile" role="tab">@lang('profile.profile')</a>
                    <a class="list-group-item list-group-item-action" id="list-account-list"
                       href="{{ route('edit-profile', ['id' => $user->id]) }}#list-account" role="tab">@lang('profile.account')</a>
                    <a class="list-group-item list-group-item-action" id="list-email-preferences-list"
                       href="{{ route('edit-profile', ['id' => $user->id]) }}#list-email-preferences" role="tab">@lang('profile.email_preferences')</a>
                    <a class="list-group-item list-group-item-action" id="list-calendar-links-list"
                       href="{{ route('edit-profile', ['id' => $user->id]) }}#list-calendar-links" role="tab">@lang('profile.calendars.title')</a>
                    <a class="list-group-item list-group-item-action active" id="list-notifications-list"
                       data-toggle="list" href="#list-notifications" role="tab" aria-controls="list-notifications">@lang('profile.notifications')</a>
                    @if(Auth::user()->isRepairDirectoryRegionalAdmin() || Auth::user()->isRepairDirectorySuperAdmin())
                    <a class="list-group-item list-group-item-action" id="list-repair-directory-list"
                       href="{{ route('edit-profile', ['id' => $user->id]) }}#list-repair-directory" role="tab">@lang('profile.repair_directory')</a>
                    @endif
                </div>
            </div>
            <div class="col-lg-8" aria-labelledby="list-profile-list">
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="list-notifications" role="tabpanel"
                         aria-labelledby="list-notifications-list">
                        <div class="edit-panel notifications-page">
                            <div class="form-row">
                                <div class="col-lg-12">
                                    <h3 class="pull-left">@lang('notifications.notifications')</h3>
                                    <a href="{{ route('markAsRead') }}" class="btn-mark-all pull-right" style="float:right">@lang('notifications.mark_all_as_read')</a>
                                </div>
                            </div>

                            <div class="cards">
                                @foreach ($notifications as $notification)
                                    @include('partials.notification')
                                @endforeach
                            </div>

                            <div class="d-flex justify-content-center">
                                {{ $notifications }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
