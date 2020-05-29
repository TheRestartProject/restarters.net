@extends('layouts.app')
@section('content')
    <div class="container">
        <div class="row">
            <div class="col">
                <div class="d-flex justify-content-between">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{{ route('dashboard') }}}">FIXOMETER</a></li>
                            <li class="breadcrumb-item active" aria-current="page">@lang('profile.notifications')</li>
                        </ol>
                    </nav>
                    <div class="">
                        <a href="/profile" class="btn btn-primary btn-view">View profile</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-4 offset-lg-sidebar">
                <div class="list-group" id="list-tab" role="tablist">
                    <a class="list-group-item list-group-item-action" id="list-profile-list"
                       href="{{ route('edit-profile') }}#list-profile" role="tab">Profile</a>
                    <a class="list-group-item list-group-item-action" id="list-account-list"
                       href="{{ route('edit-profile') }}#list-account" role="tab">Account</a>
                    <a class="list-group-item list-group-item-action" id="list-email-preferences-list"
                       href="{{ route('edit-profile') }}#list-email-preferences" role="tab">Email preferences</a>
                    <a class="list-group-item list-group-item-action" id="list-calendar-links-list"
                       href="{{ route('edit-profile') }}#list-calendar-links" role="tab">Calendars</a>
                    <a class="list-group-item list-group-item-action active" id="list-notifications-list"
                       data-toggle="list" href="#list-notifications" role="tab" aria-controls="list-notifications">@lang('notifications.notifications')</a>
                </div>
            </div>
            <div class="col-lg-8" aria-labelledby="list-profile-list">
                <div class="tab-content" id="nav-tabContent">
                    <div class="tab-pane fade show active" id="list-notifications" role="tabpanel"
                         aria-labelledby="list-notifications-list">
                        <div class="edit-panel notifications-page">
                            <div class="form-row">
                                <div class="col-lg-12">
                                    <h4 class="pull-left">@lang('notifications.notifications')</h4>
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
@endsection
