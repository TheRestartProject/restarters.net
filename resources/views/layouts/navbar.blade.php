{{-- Start Navigation --}}
<nav class="nav-wrapper">

{{-- Logo --}}
<a href="{{ route('home') }}" class="icon-brand">
    <div class="d-none d-md-block">
    @include('includes/logo')
    </div>

    <div class="d-dlock d-md-none">
    @include('includes/logo-plain')
    </div>
</a>

{{-- Left side of the Navigation --}}
<ul class="nav-left">
    <li>

        <a href="{{{ env('DISCOURSE_URL')}}}/session/sso?return_path={{{ env('DISCOURSE_URL') }}}" rel="noopener noreferrer">
        @include('svgs/navigation/talk-icon')
        <span>@lang('general.menu_discourse')</span>
    </a>
    </li>

    <li class="@if(str_contains(url()->current(), route('devices'))) active @endif">
    <a href="{{ route('devices') }}">
        @include('svgs/navigation/drill-icon')
        <span>@lang('general.menu_fixometer')</span>
    </a>
    </li>

    <li class="@if(str_contains(url()->current(), route('events'))) active @endif">
    <a href="{{ route('events') }}">
        @include('svgs/navigation/events-icon')
        <span>@lang('general.menu_events')</span>
    </a>
    </li>

    <li class="@if(str_contains(url()->current(), route('groups'))) active @endif">
    <a href="{{ route('groups') }}">
        @include('svgs/navigation/groups-icon')
        <span>@lang('general.menu_groups')</span>
    </a>
    </li>

    <li>
        <a href="{{config('restarters.wiki.base_url') }}" rel="noopener noreferrer">
        @include('svgs/navigation/wiki-icon')
        <span>@lang('general.menu_wiki')</span>
    </a>
    </li>
</ul>

                <!-- Right Side Of Navbar -->
                <ul class="nav-right">
                    <!-- Authentication Links -->
                            @php( $user = Auth::user() )
                            <li class="d-flex">
                                <div class="badge-group">
                                    @if( is_null($total_talk_notifications) )
                                        <a href="{{{ env('DISCOURSE_URL') }}}/session/sso?return_path={{{ env('DISCOURSE_URL') }}}" id="badge-talk-notifications" class="badge badge-talk-notifications  badge-pill badge-info badge-left badge-no-notifications">
                                    @else
                                            <a href="{{{ env('DISCOURSE_URL') }}}/session/sso?return_path={{{ env('DISCOURSE_URL') }}}/u/{{{ Auth::user()->username }}}/notifications" id="badge-talk-notifications" class="badge badge-pill badge-info badge-left @if( $total_talk_notifications == 0 ) badge-no-notifications @endif">
                                    @endif
                                                        <svg width="22" height="20" aria-hidden="true" data-prefix="fas" data-icon="comments" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512" class="svg-inline--fa fa-comments fa-w-18 fa-2x"><path fill="currentColor" d="M416 192c0-88.4-93.1-160-208-160S0 103.6 0 192c0 34.3 14.1 65.9 38 92-13.4 30.2-35.5 54.2-35.8 54.5-2.2 2.3-2.8 5.7-1.5 8.7S4.8 352 8 352c36.6 0 66.9-12.3 88.7-25 32.2 15.7 70.3 25 111.3 25 114.9 0 208-71.6 208-160zm122 220c23.9-26 38-57.7 38-92 0-66.9-53.5-124.2-129.3-148.1.9 6.6 1.3 13.3 1.3 20.1 0 105.9-107.7 192-240 192-10.8 0-21.3-.8-31.7-1.9C207.8 439.6 281.8 480 368 480c41 0 79.1-9.2 111.3-25 21.8 12.7 52.1 25 88.7 25 3.2 0 6.1-1.9 7.3-4.8 1.3-2.9.7-6.3-1.5-8.7-.3-.3-22.4-24.2-35.8-54.5z"></path></svg>
                                        <span class="chat-count">@if( is_null($total_talk_notifications) ) 0 @else {{{ $total_talk_notifications }}} @endif</span>
                                    </a>
                                    <div>
                                        <button id= "notifications-badge" class="badge badge-pill badge-info badge-right @if($user->unReadNotifications->count() == 0) badge-no-notifications @endif" data-toggle="collapse" data-target="#notifications" aria-expanded="false" aria-controls="notifications"><svg width="14" height="20" viewBox="0 0 11 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#fff"><ellipse cx="5.25" cy="4.868" rx="3.908" ry="3.94"/><path d="M4.158 13.601h2.184v.246h-.001A1.097 1.097 0 0 1 5.25 15a1.097 1.097 0 0 1-1.092-1.101l.001-.052h-.001v-.246z"/><ellipse cx=".671" cy="12.337" rx=".671" ry=".677"/><path d="M.671 11.66h9.158v1.353H.671z"/><ellipse cx="5.25" cy=".927" rx=".92" ry=".927"/><ellipse cx="9.829" cy="12.337" rx=".671" ry=".677"/><path d="M1.342 4.439h7.815v8.574H1.342z"/><path d="M0 12.337h10.5v.677H0z"/></g></svg>
                                        <span class="count">{{{ $user->unReadNotifications->count() }}}</span>
                                        </button>
                                    </div>
                                </div><!-- /badge-group -->
                            </li>

                        <li class="nav-item dropdown @if(str_contains(url()->current(), route('profile'))) active @endif">
                            @if (!is_null($user))
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-target="#account-nav" aria-controls="account-nav" data-toggle="collapse" aria-haspopup="true" aria-expanded="false" aria-label="Toggle account navigation" v-pre>
                                @if ( isset( $user->getProfile($user->id)->path ) && !is_null( $user->getProfile($user->id)->path ) )
                                    <img src="/uploads/thumbnail_{{ $user->getProfile($user->id)->path }}" alt="{{ Auth::user()->name }} Profile Picture" class="avatar">
                                @else
                                    <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{ Auth::user()->name }} Profile Picture" class="avatar">
                                @endif
                                </a>

                            <div id="account-nav" class="dropdown-menu collapse navbar-dropdown" aria-labelledby="navbarDropdown">

                                <ul>
                                    @if ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasPermission('verify-translation-access') || FixometerHelper::hasRole(Auth::user(), 'NetworkCoordinator') )
                                        <li><span><svg width="15" height="15" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M5.625 1.185a4.456 4.456 0 0 1 4.454 4.454 4.456 4.456 0 0 1-4.454 4.454 4.456 4.456 0 0 1-4.454-4.454 4.456 4.456 0 0 1 4.454-4.454zm0 2.28a2.175 2.175 0 0 1 0 4.347 2.174 2.174 0 0 1 0-4.347z"/><ellipse cx="4.854" cy=".162" rx=".205" ry=".162"/><ellipse cx="6.396" cy=".162" rx=".205" ry=".162"/><path d="M4.854 0h1.542v1.046H4.854z"/><path d="M6.601.162H4.649l-.206 1.172h2.364L6.601.162z"/><ellipse cx="6.396" cy="11.088" rx=".205" ry=".162"/><ellipse cx="4.854" cy="11.088" rx=".205" ry=".162"/><path d="M4.854 10.204h1.542v1.046H4.854z"/><path d="M4.649 11.088h1.952l.206-1.172H4.443l.206 1.172zM1.102 2.193c.081-.081.197-.094.261-.031.063.064.049.18-.031.26-.08.081-.196.094-.26.031-.063-.064-.05-.18.03-.26zm1.091-1.091c.08-.08.196-.093.26-.03.063.064.05.18-.031.26-.08.08-.196.094-.26.031-.063-.064-.05-.18.031-.261z"/><path d="M2.193 1.102L1.102 2.193l.74.739 1.09-1.09-.739-.74z"/><path d="M2.453 1.072L1.072 2.453l.683.973 1.671-1.671-.973-.683zm7.695 7.985c-.081.081-.197.094-.261.031-.063-.064-.049-.18.031-.26.08-.081.196-.094.26-.031.063.064.05.18-.03.26zm-1.091 1.091c-.08.08-.196.093-.26.03-.063-.064-.05-.18.031-.26.08-.08.196-.094.26-.031.063.064.05.18-.031.261z"/><path d="M9.057 10.148l1.091-1.091-.74-.739-1.09 1.09.739.74z"/><path d="M8.797 10.178l1.381-1.381-.683-.973-1.671 1.671.973.683zM0 6.396c0-.114.073-.206.162-.206.09 0 .163.092.163.206 0 .113-.073.205-.163.205-.089 0-.162-.092-.162-.205zm0-1.542c0-.113.073-.205.162-.205.09 0 .163.092.163.205 0 .114-.073.206-.163.206C.073 5.06 0 4.968 0 4.854z"/><path d="M0 4.854v1.542h1.046V4.854H0z"/><path d="M.162 4.649v1.952l1.172.206V4.443l-1.172.206zm11.088.205c0 .114-.073.206-.162.206-.09 0-.163-.092-.163-.206 0-.113.073-.205.163-.205.089 0 .162.092.162.205zm0 1.542c0 .113-.073.205-.162.205-.09 0-.163-.092-.163-.205 0-.114.073-.206.163-.206.089 0 .162.092.162.206z"/><path d="M11.25 6.396V4.854h-1.046v1.542h1.046z"/><path d="M11.088 6.601V4.649l-1.172-.206v2.364l1.172-.206zm-8.895 3.547c-.081-.081-.094-.197-.031-.261.064-.063.18-.049.26.031.081.08.094.196.031.26-.064.063-.18.05-.26-.03zM1.102 9.057c-.08-.08-.093-.196-.03-.26.064-.063.18-.05.26.031.08.08.094.196.031.26-.064.063-.18.05-.261-.031z"/><path d="M1.102 9.057l1.091 1.091.739-.74-1.09-1.09-.74.739z"/><path d="M1.072 8.797l1.381 1.381.973-.683-1.671-1.671-.683.973zm7.985-7.695c.081.081.094.197.031.261-.064.063-.18.049-.26-.031-.081-.08-.094-.196-.031-.26.064-.063.18-.05.26.03zm1.091 1.091c.08.08.093.196.03.26-.064.063-.18.05-.26-.031-.08-.08-.094-.196-.031-.26.064-.063.18-.05.261.031z"/><path d="M10.148 2.193L9.057 1.102l-.739.74 1.09 1.09.74-.739z"/><path d="M10.178 2.453L8.797 1.072l-.973.683 1.671 1.671.683-.973z"/></g></svg>Administrator</span>
                                            <ul>
                                                @if ( FixometerHelper::hasRole(Auth::user(), 'Administrator') )
                                                <li><a href="{{ route('brands') }}">Brands</a></li>
                                                <li><a href="{{ route('skills') }}">Skills</a></li>
                                                <li><a href="{{ route('tags') }}">Group tags</a></li>
                                                <li><a href="{{ route('category') }}">Categories</a></li>
                                                <li><a href="{{ route('users') }}">Users</a></li>
                                                <li><a href="{{ route('roles') }}">Roles</a></li>
                                                <li><a href="{{ route('networks.index') }}">@lang('networks.general.networks')</a></li>
                                                @endif
                                                @if ( FixometerHelper::hasPermission('verify-translation-access') )
                                                <li><a href="/translations/view/admin">Translations</a></li>
                                                @endif
                                                @if ( FixometerHelper::hasRole(Auth::user(), 'NetworkCoordinator') )
                                                    @if (count(Auth::user()->networks) == 1)
                                                        @php( $network = Auth::user()->networks->first() )
                                                    <li><a href="{{ route('networks.show', $network->id) }}">@lang('networks.general.particular_network', ['networkName' => $network->name])</a></li>
                                                    @else
                                                    <li><a href="{{ route('networks.index') }}">@lang('networks.general.networks')</a></li>
                                                    @endif
                                                @endif
                                            </ul>
                                        </li>
                                    @endif
                                    @if ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'Host') )
                                        <li><span><svg width="19" height="13" viewBox="0 0 15 11" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M1.598 7.937a1.053 1.053 0 1 1 .438 2.06 1.053 1.053 0 0 1-.438-2.06zm2.403-4.869a1.224 1.224 0 0 1 .509 2.393 1.223 1.223 0 1 1-.509-2.393z"/><path d="M4.51 5.461L3.133 3.777.865 8.514l1.902.909L4.51 5.461z"/><path d="M3.991 5.241l1.249-1.68 3.131 3.637-.926 1.966-3.454-3.923z"/><path d="M9.389 9.035l2.77-5.008-1.611-1.054-2.578 4.47 1.419 1.592z"/><path d="M13.393.265l-.351-.188-4.009 2.706.024.394 4.001 2.159.335-.22V.265z"/><circle cx="8.371" cy="8.4" r="1.202"/><path d="M9.12 2.748a.229.229 0 1 1-.185.265.229.229 0 0 1 .185-.265zM13.124.04a.23.23 0 0 1 .08.451.23.23 0 0 1-.265-.186.228.228 0 0 1 .185-.265zm.001 4.868a.229.229 0 1 1 .08.45.229.229 0 0 1-.08-.45z"/></g></svg> @lang('general.reporting')</span>
                                            <ul>
                                            @if ( FixometerHelper::hasRole(Auth::user(), 'Administrator'))
                                                <!-- temporarily adding 'a' onto the query string to avoid bug on time reporting page -->
                                                <li><a href="/reporting/time-volunteered?a">@lang('general.time_reporting')</a></li>
                                            @endif
                                                <li><a href="/search">@lang('general.party_reporting')</a></li>
                                            </ul>
                                        </li>
                                    @endif
                                    <li>
                                        @if ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'Host') )
                                            <span><svg width="15" height="13" viewBox="0 0 12 10" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M11.25 6.245H.002v2.25s-.038.75.208 1.066c.242.311.997.269.997.269l8.953-.011s.565.039.843-.26c.259-.278.247-.929.247-.929V6.245zm0-.625H6.887V4.618H4.365V5.62H.002V1.946s.008-.267.105-.386c.098-.12.335-.14.335-.14l10.29-.004s.237-.027.385.1c.133.114.133.43.133.43V5.62z"/><path d="M7.592 0v1.946H3.66V0h3.932zm-.705.666H4.365v.75h2.522v-.75z"/></g></svg> @lang('general.general')</span>
                                        @endif
                                        <ul>
                                            <li><a href="/profile/edit/{{{ Auth::user()->id }}}">@lang('general.profile')</a></li>
                                            <li><a href="/profile/edit/{{{ Auth::user()->id }}}#change-password">@lang('auth.change_password')</a></li>
                                            <li><a href="/logout">@lang('general.logout')</a></li>
                                        </ul>
                                    </li>
                                </ul>

                            </div>
                            @endif
                        </li>

                </ul>
            </div>

            <div class="collapse navbar-start navbar-dropdown" id="startMenu">

                <ul>
                    <li>
                        <svg width="11" height="14" viewBox="0 0 9 11" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M8.55 0H0v10.687l4.253-3.689 4.297 3.689V0z" fill="#0394a6"/></svg> @lang('general.menu_tools')
                        <ul>
                            <li><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li><a href="{{{ env('DISCOURSE_URL')}}}/session/sso?return_path={{{ env('DISCOURSE_URL') }}}" rel="noopener noreferrer">@lang('general.menu_discourse')</a></li>
                            <li><a href="{{ config('restarters.wiki.base_url') }}" rel="noopener noreferrer">@lang('general.menu_wiki')</a></li>
                            @if ( FixometerHelper::hasPermission('repair-directory') )
                                <li><a href="{{ config('restarters.repairdirectory.base_url') }}/admin" target="_blank" rel="noopener noreferrer">Repair Directory</a></li>
                            @endif
                        </ul>
                    </li>

                    <li>
                        <svg width="15" height="15" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M5.625 0a5.627 5.627 0 0 1 5.625 5.625 5.627 5.627 0 0 1-5.625 5.625A5.627 5.627 0 0 1 0 5.625 5.627 5.627 0 0 1 5.625 0zm1.19 9.35l.104-.796c-.111-.131-.301-.249-.57-.352V4.073a9.365 9.365 0 0 0-.838-.031c-.331 0-.69.045-1.076.134l-.104.797c.138.152.328.269.57.352v2.877c-.283.103-.473.221-.57.352l.104.796h2.38zM5.604 3.462c-.572 0-.859-.26-.859-.781s.288-.781.864-.781c.577 0 .865.26.865.781s-.29.781-.87.781z" fill="#0394a6"/></svg> @lang('general.menu_other')
                        <ul>
                            <li><a href="@lang('general.help_feedback_url')" target="_blank" rel="noopener noreferrer">@lang('general.menu_help_feedback')</a></li>
                            <li><a href="@lang('general.faq_url')" target="_blank" rel="noopener noreferrer">@lang('general.menu_faq')</a></li>
                            <li><a href="@lang('general.restartproject_url')" target="_blank" rel="noopener noreferrer">@lang('general.therestartproject')</a></li>
                        </ul>
                    </li>

                </ul>

            </div>

        </div>
    </nav>

    <aside id="notifications" class="notifications collapse">
        <div class="notifications__scroll">
            <div id="tabs" class="notifications__inner">

                @if( isset($user->notifications) && is_object($user->notifications) && $user->notifications->count() > 0 )
                <div class="cards">

                @foreach ($user->notifications->take(10) as $notification)
                    @include('partials.notification')
                @endforeach

                </div>
            @else
                <div class="alert alert-secondary" role="alert">
                    <h3>@lang('general.alert_uptodate')</h3>
                    <p>@lang('general.alert_uptodate_text')</p>
                </div>
            @endif
            <a href="{{ route('notifications') }}" class="notifications__older">@lang('notifications.view_all')</a>
        </div>
    </div>
</aside>
