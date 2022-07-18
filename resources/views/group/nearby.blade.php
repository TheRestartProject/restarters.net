@extends('layouts.app')
@section('title')
{{ $group->name }} - Volunteers Nearby
@endsection
@section('content')
<section class="events group-view">
    <div class="container">

        <?php if( isset($_GET['message']) && $_GET['message'] == 'invite' ): ?>
        <div class="alert alert-success" role="alert">
            Thank you, your invitation has been sent
        </div>
        <?php endif; ?>

        @if(session()->has('response'))
        @php( App\Helpers\Fixometer::printResponse(session('response')) )
        @endif

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
        @if ($has_pending_invite)
        <div class="alert alert-info">
            You have an invitation to this group. Please click 'Join Group' if you would like to join.
        </div>
        @endif

        <div class="events__header row align-content-top">
            <div class="col-lg-7 d-flex flex-column">

                <header>

                    @if( App\Helpers\Fixometer::hasRole( $user, 'Administrator' ) || ( $is_host_of_group && $user_groups > 1 ) )

                    <h1 class="sr-only">{{{ $group->name }}}</h1>
                    <button class="btn btn-title dropdown-toggle" type="button" id="dropdownTitle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        {{{ $group->name }}}
                    </button>
                    <div class="dropdown-menu dropdown-menu__titles" aria-labelledby="dropdownTitle">

                        @if( App\Helpers\Fixometer::hasRole( $user, 'Administrator' ) )

                        @foreach($grouplist as $g)
                        <a class="dropdown-item" href="{{ url('/group/view') }}/{{ $g->id }}">
                            @if(!empty($g->path))
                            <img src="{{ url('/uploads/thumbnail_'.$g->path) }}" alt="{{ $g->name }} group image" class="dropdown-item-icon">
                            @else
                            <img src="{{ url('/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg') }}" alt="{{ $g->name }} group image" class="dropdown-item-icon">
                            @endif
                            <span>{{ $g->name }}</span>
                        </a>
                        @endforeach

                        @else

                        @foreach($userGroups as $g)
                        <a class="dropdown-item" href="{{ url('/group/view/'.$g->idgroups) }}" title="Switch to {{ $g->name }}">
                            @if(!empty($g->path))
                            <img src="{{ url('/uploads/mid_'.$g->path) }}" alt="{{ $g->name }} group image" class="dropdown-item-icon">
                            @else
                            <img src="{{ url('/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg') }}" alt="{{ $g->name }} group image" class="dropdown-item-icon">
                            @endif
                            <span>{{ $g->name }}</span>
                        </a>
                        @endforeach

                        @endif

                    </div>
                    @else
                    <h1>{{{ $group->name }}}</h1>
                    @endif

                    <p>{{{ $group->location . (!empty($group->area) ? ', ' . $group->area : '') }}}</p>

                    @if( !empty($group->website) )
                    <a class="events__header__url" href="{{{ $group->website }}}" rel="noopener noreferrer">{{{ $group->website }}}</a>
                    @endif

                    @php( $groupImage = $group->groupImage )
                    @if( is_object($groupImage) && is_object($groupImage->image) )
                    <img src="{{ asset('/uploads/mid_'. $groupImage->image->path) }}" alt="{{{ $group->name }}} group image" class="event-icon">
                    @else
                    <img src="{{ url('/uploads/mid_1474993329ef38d3a4b9478841cc2346f8e131842fdcfd073b307.jpg') }}" alt="{{{ $group->name }}} group image" class="event-icon">
                    @endif

                </header>

            </div>
            <div class="col-lg-5">

                @if( App\Helpers\Fixometer::hasRole( $user, 'Administrator' ) || $is_host_of_group )
                <div class="button-group button-group__r">

                    <div class="dropdown">
                        <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Group actions
                        </button>
                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            <a class="dropdown-item" href="{{ url('/group/edit/'.$group->idgroups) }}">Edit group</a>
                            <a class="dropdown-item" href="{{ url('/party/create') }}">Add event</a>
                            <a class="dropdown-item" data-toggle="modal" data-target="#invite-to-group" href="#">Invite volunteers</a>
                            <a class="dropdown-item" href="#" data-toggle="modal" data-target="#group-share-stats">Share group stats</a>
                        </div>
                    </div>

                </div>
                @else
                <div class="button-group button-group__r">
                    @if ($in_group)
                    <a class="btn btn-primary" href="#" data-toggle="modal" data-target="#invite-to-group">Invite volunteers</a>
                    @else
                    <a class="btn btn-primary" href="/group/join/{{ $group->idgroups }}" id="join-group">@lang('groups.join_group_button')</a>
                    @endif
                    <a class="btn btn-primary" href="#" data-toggle="modal" data-target="#group-share-stats">Share group stats</a>
                </div>
                @endif

            </div>
        </div>

        <div class="row">
            <div class="col-lg-3">

                <h2 id="about-grp">About
                    @if( App\Helpers\Fixometer::hasRole( $user, 'Administrator' ) || $is_host_of_group )
                    <sup>(<a href="{{ url('/group/edit/'.$group->idgroups) }}">Edit group</a>)</sup>
                    @endif
                </h2>

                <div class="events__description">
                    <h3 class="events__side__heading" id="description">Description:</h3>
                    <p>{!! Str::limit(strip_tags($group->free_text), 160, '...') !!}</p>
                    @if( strlen($group->free_text) > 160 )
                    <button data-toggle="modal" data-target="#group-description"><span>Read more</span></button>
                    @endif
                </div><!-- /events__description -->


                @if ($in_group)
                <h2 id="volunteers">Volunteers <sup>(<a data-toggle="modal" data-target="#invite-to-group" href="#">Invite to group</a>)</sup></h2>
                @else
                <h2 id="volunteers">Volunteers <sup>(<a href="/group/join/{{ $group->idgroups }}">@lang('groups.join_group_button')</a>)</sup></h2>
                @endif

                <div class="tab">

                    <div class="users-list-wrap users-list__single">
                        <ul class="users-list">

                            @foreach( $view_group->allConfirmedVolunteers->take(3) as $volunteer )
                            @include('partials.volunteer-badge')
                            @endforeach

                        </ul>
                        @if( $view_group->allConfirmedVolunteers->count() > 3 )
                        <a class="users-list__more" href="#" data-toggle="modal" data-target="#group-volunteers">See all {{{ $view_group->allConfirmedVolunteers->count() }}} volunteers</a>
                        @endif
                    </div>

                </div>

            </div>
            <div class="col-lg-9">

                <h2 id="key-stats">Volunteers nearby</h2>

                <section class="panel">

                    <p>These volunteers are already registered on Restarters.net and within 20 miles of your group. You can invite them to join your group here. (Note: volunteers marked with a ⚠ will be invited but won't be sent an email due to their notification settings. Volunteers who have not provided a location will not appear in the list.)</p>

                    <p>Back to main <a href="{{ url('/group/view/' . $group->idgroups) }}">group page</a>.

                        <div class="users-list-wrap">
                            <ul class="users-list">
                                @foreach ($restarters_nearby as $restarter)
                                <li class="volunteer-{{ $restarter->name }}">

                                    @php( $user = $restarter )

                                    @if( is_object($user) )

                                    @php( $user_skills = $user->userSkills )

                                    <?php
        $skills_list = '';
        foreach( $user_skills as $skill ){
          $skills_list .= $skill->skillName->skill_name.', ';
        }
        $skills_list = rtrim($skills_list, ', ');
      ?>

                                    <h3><a href="/profile/{{ $user->id }}">{{ $user->name }}</a></h3>
                                    <p>{{ $user->location }}</p>

                                    @endif

                                    @if( is_object($user) )
                                    @php( $path = $user->getProfile($user->id)->path )
                                    @if ( is_null($path) )
                                    <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="Placeholder avatar" class="users-list__icon">
                                    @else
                                    <img src="{{ asset('/uploads/thumbnail_' . $path) }}" alt="{{ $user->name }}'s avatar" class="users-list__icon">
                                    @endif
                                    @else
                                    <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{ $volunteer->getFullName() }}'s avatar" class="users-list__icon">
                                    @endif

                                    @if ($restarter->hasPendingInvite)
                                    <p class="float-right">[already invited]</p>
                                    @elseif ($restarter->notAMember)
                                    <p class="float-right">
                                        @if ($restarter->invites !== 1)
                                        ⚠
                                        @endif
                                        <a href="/group/nearbyinvite/{{ $group->idgroups }}/{{ $user->id }}">Invite</a>
                                    </p>
                                    @else
                                    <p class="float-right">[already a member]</p>
                                    @endif

                                </li>
                                @endforeach
                            </ul>
                        </div>
                </section>

            </div>
        </div>
    </div>
</section>

@include('includes/modals/group-description')
@include('includes/modals/group-volunteers')
@include('includes/modals/group-share-stats')

@endsection
