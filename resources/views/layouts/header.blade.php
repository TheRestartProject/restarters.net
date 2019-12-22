<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} @hasSection('title')- @yield('title')@endif</title>

    <link rel="shortcut icon" href="/images/favicon.ico" type="image/x-icon" />

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    @yield('extra-css')

    <!-- Cookie banner with fine-grained opt-in -->
    <script src="{{ asset('js/gdpr-cookie-notice.js') }}"></script>
    <!-- Check to see if visitor has opted in to analytics cookies -->
    <script>
        window.restarters = {};
        restarters.cookie_domain = '{{ env('SESSION_DOMAIN') }}';
        var gdprCookiesCheck = Cookies;
        var gdprCurrentCookiesSelection = gdprCookiesCheck.getJSON('gdprcookienotice');
        restarters.analyticsCookieEnabled = (typeof gdprCurrentCookiesSelection !== 'undefined' && gdprCurrentCookiesSelection['analytics']);
    </script>

    <!-- Global site tag (gtag.js) - Google Analytics -->
    @if( !empty(env('GOOGLE_ANALYTICS_TRACKING_ID')) )
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ env('GOOGLE_ANALYTICS_TRACKING_ID') }}"></script>
        <script>
            if (restarters.analyticsCookieEnabled) {
                window.dataLayer = window.dataLayer || [];
                function gtag(){dataLayer.push(arguments);}
                gtag('js', new Date());

                gtag('config', '{{ env('GOOGLE_ANALYTICS_TRACKING_ID') }}');

                <!-- Google Tag Manager -->
                (function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start': new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0], j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src= 'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
                })(window,document,'script','dataLayer', '{{ env('GOOGLE_TAG_MANAGER_ID') }}');
                <!-- End Google Tag Manager -->
            }
        </script>
    @endif
  </head>
  @if( Request::is('login') || Request::is('user/register') )
    <body class="fixed-layout">
  @elseif ( isset($onboarding) && $onboarding )
    <body class="onboarding">
  @else
    <body>
  @endif
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ env('GOOGLE_TAG_MANAGER_ID') }}" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

   <nav class="navbar navbar-expand-md navbar-light navbar-laravel">
            <div class="container">
                <a class="navbar-brand d-none d-sm-block" role="button" data-toggle="collapse" aria-expanded="false" href="#startMenu" aria-controls="startMenu" aria-label="Toggle start menu">
                    @include('includes/logo')
                    <span class="caret"></span>
                </a>

                <!-- <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>-->

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav navbar-nav__left">

                        <li><a class="nav-link" href="{{{ route('events') }}}"><svg width="18" height="18" viewBox="0 0 14 14" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><path d="M12.462,13.5l-11.423,0c-0.282,0 -0.525,-0.106 -0.731,-0.318c-0.205,-0.212 -0.308,-0.463 -0.308,-0.753l0,-9.215c0,-0.29 0.103,-0.541 0.308,-0.753c0.206,-0.212 0.449,-0.318 0.731,-0.318l1.038,0l0,-0.804c0,-0.368 0.127,-0.683 0.381,-0.945c0.255,-0.263 0.56,-0.394 0.917,-0.394l0.519,0c0.357,0 0.663,0.131 0.917,0.394c0.254,0.262 0.382,0.577 0.382,0.945l0,0.804l3.115,0l0,-0.804c0,-0.368 0.127,-0.683 0.381,-0.945c0.254,-0.263 0.56,-0.394 0.917,-0.394l0.519,0c0.357,0 0.663,0.131 0.917,0.393c0.254,0.263 0.381,0.578 0.381,0.946l0,0.804l1.039,0c0.281,0 0.525,0.106 0.73,0.318c0.205,0.212 0.308,0.463 0.308,0.753l0,9.215c0,0.29 -0.103,0.541 -0.308,0.753c-0.206,0.212 -0.449,0.318 -0.73,0.318Zm-0.087,-3.805l-2.25,0l0,1.909l2.25,0l0,-1.909Zm-6,0l-2.25,0l0,1.909l2.25,0l0,-1.909Zm-3,0l-2.25,0l0,1.909l2.25,0l0,-1.909Zm6,0l-2.25,0l0,1.909l2.25,0l0,-1.909Zm3,-2.658l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-3,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm3,-2.658l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-3,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm6,0l-2.25,0l0,1.908l2.25,0l0,-1.908Zm-5.481,-3.307l-0.519,0c-0.07,0 -0.131,0.026 -0.182,0.079c-0.052,0.053 -0.077,0.116 -0.077,0.188l0,1.661c0,0.073 0.025,0.135 0.077,0.188c0.051,0.053 0.112,0.08 0.182,0.08l0.519,0c0.071,0 0.131,-0.027 0.183,-0.08c0.051,-0.053 0.077,-0.115 0.077,-0.188l0,-1.661c0,-0.072 -0.026,-0.135 -0.077,-0.188c-0.051,-0.053 -0.112,-0.079 -0.183,-0.079Zm6.231,0l-0.519,0c-0.07,0 -0.131,0.026 -0.183,0.079c-0.051,0.053 -0.077,0.116 -0.077,0.188l0,1.661c0,0.073 0.026,0.135 0.077,0.188c0.052,0.053 0.113,0.08 0.183,0.08l0.519,0c0.071,0 0.131,-0.027 0.183,-0.08c0.051,-0.053 0.077,-0.115 0.077,-0.188l0,-1.661c0,-0.072 -0.026,-0.135 -0.077,-0.188c-0.052,-0.053 -0.112,-0.079 -0.183,-0.079Z" style="fill:#0394a6;fill-rule:nonzero;"/></svg>@lang('general.top_item1')</a></li>

                        <li><a class="nav-link" href="{{{ route('devices') }}}"><svg width="20" height="18" style="margin-top: -9px;" viewBox="0 0 15 14" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M13.528 13.426H1.472C.66 13.426 0 12.766 0 11.954V4.021c0-.812.66-1.472 1.472-1.472h4.686L4.732.514a.19.19 0 0 1 .047-.263l.309-.217a.188.188 0 0 1 .263.047L7.08 2.549h.925L9.733.081a.188.188 0 0 1 .263-.047l.31.217c.085.06.106.177.046.263L8.927 2.549h4.601c.812 0 1.472.66 1.472 1.472v7.933c0 .812-.66 1.472-1.472 1.472zM9.516 3.927H2.473c-.607 0-1.099.492-1.099 1.099v5.923c0 .607.492 1.099 1.099 1.099h7.043a1.1 1.1 0 0 0 1.099-1.099V5.026a1.1 1.1 0 0 0-1.099-1.099zm3.439 3.248a.813.813 0 1 1-.001 1.625.813.813 0 0 1 .001-1.625zm0-2.819a.813.813 0 1 1-.001 1.625.813.813 0 0 1 .001-1.625z" fill="#0394a6"/></svg>@lang('general.top_item2')</a></li>

                        <li><a class="nav-link" href="{{{ route('groups') }}}"><svg width="18" height="18" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M1.849 2.15S2.332.134 5.326.134c0 0 1.037-.03 2.048.513 1.029.553 1.656.739 2.706.739S12.634 1.134 13.5 0v6.87s-.772 2.202-3.449 2.202c0 0-1.314.036-2.252-.593-.643-.431-1.271-.649-2.558-.644-1.286.005-2.834.69-3.392 1.338V2.15zM0 .398h1.125V13.5H0z" fill="#0394a6"/></svg>@lang('general.top_item3')</a></li>

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
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

                            <li class="nav-item dropdown">
                                @if (!is_null($user))
                                  <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-target="#account-nav" aria-controls="account-nav" data-toggle="collapse" aria-haspopup="true" aria-expanded="false" aria-label="Toggle account navigation" v-pre>
                                    @if ( isset( $user->getProfile($user->id)->path ) && !is_null( $user->getProfile($user->id)->path ) )
                                      <img src="/uploads/thumbnail_{{ $user->getProfile($user->id)->path }}" alt="{{ Auth::user()->name }} Profile Picture" class="avatar">
                                    @else
                                      <img src="{{ asset('/images/placeholder-avatar.png') }}" alt="{{ Auth::user()->name }} Profile Picture" class="avatar">
                                    @endif
                                     <span class="user-name">{{ Auth::user()->name }}</span> <span class="caret"></span>
                                  </a>

                                <div id="account-nav" class="dropdown-menu collapse navbar-dropdown" aria-labelledby="navbarDropdown">

                                    <ul>
                                        @if ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasPermission('verify-translation-access') )
                                          <li><svg width="15" height="15" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M5.625 1.185a4.456 4.456 0 0 1 4.454 4.454 4.456 4.456 0 0 1-4.454 4.454 4.456 4.456 0 0 1-4.454-4.454 4.456 4.456 0 0 1 4.454-4.454zm0 2.28a2.175 2.175 0 0 1 0 4.347 2.174 2.174 0 0 1 0-4.347z"/><ellipse cx="4.854" cy=".162" rx=".205" ry=".162"/><ellipse cx="6.396" cy=".162" rx=".205" ry=".162"/><path d="M4.854 0h1.542v1.046H4.854z"/><path d="M6.601.162H4.649l-.206 1.172h2.364L6.601.162z"/><ellipse cx="6.396" cy="11.088" rx=".205" ry=".162"/><ellipse cx="4.854" cy="11.088" rx=".205" ry=".162"/><path d="M4.854 10.204h1.542v1.046H4.854z"/><path d="M4.649 11.088h1.952l.206-1.172H4.443l.206 1.172zM1.102 2.193c.081-.081.197-.094.261-.031.063.064.049.18-.031.26-.08.081-.196.094-.26.031-.063-.064-.05-.18.03-.26zm1.091-1.091c.08-.08.196-.093.26-.03.063.064.05.18-.031.26-.08.08-.196.094-.26.031-.063-.064-.05-.18.031-.261z"/><path d="M2.193 1.102L1.102 2.193l.74.739 1.09-1.09-.739-.74z"/><path d="M2.453 1.072L1.072 2.453l.683.973 1.671-1.671-.973-.683zm7.695 7.985c-.081.081-.197.094-.261.031-.063-.064-.049-.18.031-.26.08-.081.196-.094.26-.031.063.064.05.18-.03.26zm-1.091 1.091c-.08.08-.196.093-.26.03-.063-.064-.05-.18.031-.26.08-.08.196-.094.26-.031.063.064.05.18-.031.261z"/><path d="M9.057 10.148l1.091-1.091-.74-.739-1.09 1.09.739.74z"/><path d="M8.797 10.178l1.381-1.381-.683-.973-1.671 1.671.973.683zM0 6.396c0-.114.073-.206.162-.206.09 0 .163.092.163.206 0 .113-.073.205-.163.205-.089 0-.162-.092-.162-.205zm0-1.542c0-.113.073-.205.162-.205.09 0 .163.092.163.205 0 .114-.073.206-.163.206C.073 5.06 0 4.968 0 4.854z"/><path d="M0 4.854v1.542h1.046V4.854H0z"/><path d="M.162 4.649v1.952l1.172.206V4.443l-1.172.206zm11.088.205c0 .114-.073.206-.162.206-.09 0-.163-.092-.163-.206 0-.113.073-.205.163-.205.089 0 .162.092.162.205zm0 1.542c0 .113-.073.205-.162.205-.09 0-.163-.092-.163-.205 0-.114.073-.206.163-.206.089 0 .162.092.162.206z"/><path d="M11.25 6.396V4.854h-1.046v1.542h1.046z"/><path d="M11.088 6.601V4.649l-1.172-.206v2.364l1.172-.206zm-8.895 3.547c-.081-.081-.094-.197-.031-.261.064-.063.18-.049.26.031.081.08.094.196.031.26-.064.063-.18.05-.26-.03zM1.102 9.057c-.08-.08-.093-.196-.03-.26.064-.063.18-.05.26.031.08.08.094.196.031.26-.064.063-.18.05-.261-.031z"/><path d="M1.102 9.057l1.091 1.091.739-.74-1.09-1.09-.74.739z"/><path d="M1.072 8.797l1.381 1.381.973-.683-1.671-1.671-.683.973zm7.985-7.695c.081.081.094.197.031.261-.064.063-.18.049-.26-.031-.081-.08-.094-.196-.031-.26.064-.063.18-.05.26.03zm1.091 1.091c.08.08.093.196.03.26-.064.063-.18.05-.26-.031-.08-.08-.094-.196-.031-.26.064-.063.18-.05.261.031z"/><path d="M10.148 2.193L9.057 1.102l-.739.74 1.09 1.09.74-.739z"/><path d="M10.178 2.453L8.797 1.072l-.973.683 1.671 1.671.683-.973z"/></g></svg>Administrator
                                              <ul>
                                                  @if ( FixometerHelper::hasRole(Auth::user(), 'Administrator') )
                                                    <li><a href="{{ route('brands') }}">Brands</a></li>
                                                    <li><a href="{{ route('skills') }}">Skills</a></li>
                                                    <li><a href="{{ route('tags') }}">Group tags</a></li>
                                                    <li><a href="{{ route('category') }}">Categories</a></li>
                                                    <li><a href="{{ route('users') }}">Users</a></li>
                                                    <li><a href="{{ route('roles') }}">Roles</a></li>
                                                  @endif
                                                  @if ( FixometerHelper::hasPermission('verify-translation-access') )
                                                    <li><a href="/translations">Translations</a></li>
                                                  @endif
                                              </ul>
                                          </li>
                                        @endif
                                        @if ( FixometerHelper::hasRole(Auth::user(), 'Administrator') || FixometerHelper::hasRole(Auth::user(), 'Host') )
                                          <li><svg width="19" height="13" viewBox="0 0 15 11" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M1.598 7.937a1.053 1.053 0 1 1 .438 2.06 1.053 1.053 0 0 1-.438-2.06zm2.403-4.869a1.224 1.224 0 0 1 .509 2.393 1.223 1.223 0 1 1-.509-2.393z"/><path d="M4.51 5.461L3.133 3.777.865 8.514l1.902.909L4.51 5.461z"/><path d="M3.991 5.241l1.249-1.68 3.131 3.637-.926 1.966-3.454-3.923z"/><path d="M9.389 9.035l2.77-5.008-1.611-1.054-2.578 4.47 1.419 1.592z"/><path d="M13.393.265l-.351-.188-4.009 2.706.024.394 4.001 2.159.335-.22V.265z"/><circle cx="8.371" cy="8.4" r="1.202"/><path d="M9.12 2.748a.229.229 0 1 1-.185.265.229.229 0 0 1 .185-.265zM13.124.04a.23.23 0 0 1 .08.451.23.23 0 0 1-.265-.186.228.228 0 0 1 .185-.265zm.001 4.868a.229.229 0 1 1 .08.45.229.229 0 0 1-.08-.45z"/></g></svg> @lang('general.reporting')
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
                                              <svg width="15" height="13" viewBox="0 0 12 10" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M11.25 6.245H.002v2.25s-.038.75.208 1.066c.242.311.997.269.997.269l8.953-.011s.565.039.843-.26c.259-.278.247-.929.247-.929V6.245zm0-.625H6.887V4.618H4.365V5.62H.002V1.946s.008-.267.105-.386c.098-.12.335-.14.335-.14l10.29-.004s.237-.027.385.1c.133.114.133.43.133.43V5.62z"/><path d="M7.592 0v1.946H3.66V0h3.932zm-.705.666H4.365v.75h2.522v-.75z"/></g></svg> @lang('general.general')
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
                                <li><a href="{{ env('DISCOURSE_URL') }}/login" target="_blank" rel="noopener noreferrer">@lang('general.menu_discourse')</a></li>
                                <li><a href="@lang('general.wiki_url')" target="_blank" rel="noopener noreferrer">@lang('general.menu_wiki')</a></li>
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
                                <!--<li><a role="button" data-toggle="modal" href="#onboarding" data-target="#onboarding">Welcome</a></li>-->
                            </ul>
                        </li>

                    </ul>

                </div>

            </div>
        </nav>

        <aside id="notifications" class="notifications collapse">
            <div class="notifications__scroll">
                <div id="tabs" class="notifications__inner">

                  @if( isset($user->notifications) && is_object($user->notifications) && $user->unReadNotifications->count() > 0 )
                  <div class="cards">

                    @foreach ($user->unReadNotifications as $notification)
                    <div class="card status-read {{{ FixometerHelper::notificationClasses($notification->type) }}}">
                        <div class="card-body">
                            <h5 class="card-title mb-1">
                                {{{ $notification->data['title'] }}}

                                @if (!empty($notification->data['url']))
                                    <a href="{{{ $notification->data['url'] }}}">{{{ $notification->data['name'] }}}</a>
                                @else
                                    {{{ $notification->data['name'] }}}
                                @endif
                            </h5>
                            <time title="{{{ $notification->created_at->toDayDateTimeString() }}}">{{{ $notification->created_at->diffForHumans() }}}</time>
                            <div class="d-flex flex-row justify-content-end mt-1">
                              <a href="{{ route('markAsRead', ['id' => $notification->id]) }}" class="btn-marked">Mark as read</a>
                              <span class="marked-as-read"><svg width="13px" height="9" viewBox="0 0 54 37" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Green tick icon</title><path d="M4.615 14.064a.969.969 0 0 0-1.334 0l-3 2.979a.868.868 0 0 0 0 1.279l18.334 18c.333.35.916.35 1.291 0l3.042-2.983a.869.869 0 0 0 0-1.28L4.615 14.064z" fill="#0394a6"/><path d="M53.365 4.584a.913.913 0 0 0 .041-1.287L50.365.272c-.334-.358-.959-.363-1.292-.013L15.99 32.109a.873.873 0 0 0 0 1.284l3 3.029a.97.97 0 0 0 1.333.012l33.042-31.85z" fill="#0394a6"/></svg> Marked as read</span>
                            </div>
                        </div>
                    </div>
                    @endforeach

                  </div>
                @else
                  <div class="alert alert-secondary" role="alert">
                      <h3>@lang('general.alert_uptodate')</h3>
                      <p>@lang('general.alert_uptodate_text')</p>
                  </div>
                @endif
                <div class="cards">

                  @foreach ($user->readNotifications as $notification)
                  <div class="card status-is-read {{{ FixometerHelper::notificationClasses($notification->type) }}}" style="display: none;">
                      <div class="card-body">
                          @if (!empty($notification->data['url']))
                              <h5 class="card-title mb-1">{{{ $notification->data['title'] }}} <a href="{{{ $notification->data['url'] }}}">{{{ $notification->data['name'] }}}</a></h5>
                          @else
                              <h5 class="card-title mb-1">{{{ $notification->data['title'] }}} {{{ $notification->data['name'] }}}</h5>
                          @endif

                          <time title="{{{ $notification->created_at->toDayDateTimeString() }}}">{{{ $notification->created_at->diffForHumans() }}}</time>
                          <div class="d-flex flex-row justify-content-end mt-1">
                            <span class="marked-as-read"><svg width="13px" height="9" viewBox="0 0 54 37" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>Green tick icon</title><path d="M4.615 14.064a.969.969 0 0 0-1.334 0l-3 2.979a.868.868 0 0 0 0 1.279l18.334 18c.333.35.916.35 1.291 0l3.042-2.983a.869.869 0 0 0 0-1.28L4.615 14.064z" fill="#0394a6"/><path d="M53.365 4.584a.913.913 0 0 0 .041-1.287L50.365.272c-.334-.358-.959-.363-1.292-.013L15.99 32.109a.873.873 0 0 0 0 1.284l3 3.029a.97.97 0 0 0 1.333.012l33.042-31.85z" fill="#0394a6"/></svg> Marked as read</span>
                          </div>
                      </div>
                  </div>
                  @endforeach

                </div>
                <button class="notifications__older js-load">View read notifications</button>
            </div>
        </div>
    </aside>
