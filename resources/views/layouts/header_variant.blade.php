<!doctype html>
<html class="body-plain" lang="{{ app()->getLocale() }}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Laravel</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Raleway:100,600" rel="stylesheet" type="text/css">

        <!-- Styles -->
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">

        <style>
            html, body {
                font-family: 'Raleway', sans-serif;
                font-weight: 400;
                height: 100vh;
                margin: 0;
            }

            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }
        </style>
    </head>
    @if ( isset($onboarding) && $onboarding )
      <body class="onboarding">
    @else
      <body>
    @endif

   <nav class="navbar navbar-expand-md navbar-light navbar-laravel">
            <div class="container">
                <a class="navbar-brand" role="button" data-toggle="collapse" aria-expanded="false" href="#startMenu" aria-controls="startMenu" aria-label="Toggle start menu">
                    @include('includes.logo')
                    <span class="caret">&#9660;</span>
                </a>

                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav navbar-nav__left">
                        <li><a class="nav-link" href="{{ url('/') }}/parties"><svg width="18" height="20" viewBox="0 0 14 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6" fill-rule="nonzero"><path d="M6.762 3.459a1.153 1.153 0 0 0 .989-1.747L6.762 0l-.989 1.712a1.154 1.154 0 0 0 .989 1.747zm5.117 2.677H7.428V4.023H6.089v2.113l-4.392.001C.589 6.137 0 6.773 0 7.901v1.438c0 .736.589 1.052 1.312 1.052.351 0 .679-.14.926-.392L3.86 8.62l1.975 1.376a1.326 1.326 0 0 0 1.854 0l1.786-1.247 1.81 1.247c.248.252.576.392.927.392.723 0 1.312-.412 1.312-1.149l-.007-1.457c-.007-1.128-.53-1.646-1.638-1.646z"/><path d="M10.569 10.722l-1.101-.62-1.077.62c-.873.89-2.399.89-3.272 0l-1.266-.749-.722.733c-.432.446-1.2.708-1.819.708-.486 0-.937-.157-1.312-.419v3.323c0 .375.301.682.669.682h12.179a.678.678 0 0 0 .669-.682v-3.323a2.276 2.276 0 0 1-1.311.419 2.28 2.28 0 0 1-1.637-.692z"/></g></svg>@lang('general.top_item1')</a></li>
                        <li><a class="nav-link" href="{{ url('/') }}/devices"><svg width="20" height="18" viewBox="0 0 15 14" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M13.528 13.426H1.472C.66 13.426 0 12.766 0 11.954V4.021c0-.812.66-1.472 1.472-1.472h4.686L4.732.514a.19.19 0 0 1 .047-.263l.309-.217a.188.188 0 0 1 .263.047L7.08 2.549h.925L9.733.081a.188.188 0 0 1 .263-.047l.31.217c.085.06.106.177.046.263L8.927 2.549h4.601c.812 0 1.472.66 1.472 1.472v7.933c0 .812-.66 1.472-1.472 1.472zM9.516 3.927H2.473c-.607 0-1.099.492-1.099 1.099v5.923c0 .607.492 1.099 1.099 1.099h7.043a1.1 1.1 0 0 0 1.099-1.099V5.026a1.1 1.1 0 0 0-1.099-1.099zm3.439 3.248a.813.813 0 1 1-.001 1.625.813.813 0 0 1 .001-1.625zm0-2.819a.813.813 0 1 1-.001 1.625.813.813 0 0 1 .001-1.625z" fill="#0394a6"/></svg>@lang('general.top_item2')</a></li>
                        <li><a class="nav-link" href="{{ url('/') }}/groups"><svg width="18" height="18" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M1.849 2.15S2.332.134 5.326.134c0 0 1.037-.03 2.048.513 1.029.553 1.656.739 2.706.739S12.634 1.134 13.5 0v6.87s-.772 2.202-3.449 2.202c0 0-1.314.036-2.252-.593-.643-.431-1.271-.649-2.558-.644-1.286.005-2.834.69-3.392 1.338V2.15zM0 .398h1.125V13.5H0z" fill="#0394a6"/></svg>@lang('general.top_item3')</a></li>
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->

                            <!--<li><a class="nav-link" href="{{ url('/') }}/login">@lang('general.login')</a></li>
                            <li><a class="nav-link" href="{{ url('/') }}/register">@lang('general.register')</a></li>-->

                            <li><button class="badge badge-pill badge-info" data-toggle="collapse" data-target="#notifications" aria-expanded="false" aria-controls="notifications"><svg width="14" height="20" viewBox="0 0 11 15" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#fff"><ellipse cx="5.25" cy="4.868" rx="3.908" ry="3.94"/><path d="M4.158 13.601h2.184v.246h-.001A1.097 1.097 0 0 1 5.25 15a1.097 1.097 0 0 1-1.092-1.101l.001-.052h-.001v-.246z"/><ellipse cx=".671" cy="12.337" rx=".671" ry=".677"/><path d="M.671 11.66h9.158v1.353H.671z"/><ellipse cx="5.25" cy=".927" rx=".92" ry=".927"/><ellipse cx="9.829" cy="12.337" rx=".671" ry=".677"/><path d="M1.342 4.439h7.815v8.574H1.342z"/><path d="M0 12.337h10.5v.677H0z"/></g></svg> <span>152</span></button></li>

                            <li class="nav-item dropdown">

                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                <img src="" alt="user" class="avatar">
                                   @lang('general.user') <span class="caret"></span>
                                </a>

                                <div class="dropdown-menu collapse navbar-dropdown" aria-labelledby="navbarDropdown">

                                    <ul>
                                        <li><svg width="15" height="15" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M5.625 1.185a4.456 4.456 0 0 1 4.454 4.454 4.456 4.456 0 0 1-4.454 4.454 4.456 4.456 0 0 1-4.454-4.454 4.456 4.456 0 0 1 4.454-4.454zm0 2.28a2.175 2.175 0 0 1 0 4.347 2.174 2.174 0 0 1 0-4.347z"/><ellipse cx="4.854" cy=".162" rx=".205" ry=".162"/><ellipse cx="6.396" cy=".162" rx=".205" ry=".162"/><path d="M4.854 0h1.542v1.046H4.854z"/><path d="M6.601.162H4.649l-.206 1.172h2.364L6.601.162z"/><ellipse cx="6.396" cy="11.088" rx=".205" ry=".162"/><ellipse cx="4.854" cy="11.088" rx=".205" ry=".162"/><path d="M4.854 10.204h1.542v1.046H4.854z"/><path d="M4.649 11.088h1.952l.206-1.172H4.443l.206 1.172zM1.102 2.193c.081-.081.197-.094.261-.031.063.064.049.18-.031.26-.08.081-.196.094-.26.031-.063-.064-.05-.18.03-.26zm1.091-1.091c.08-.08.196-.093.26-.03.063.064.05.18-.031.26-.08.08-.196.094-.26.031-.063-.064-.05-.18.031-.261z"/><path d="M2.193 1.102L1.102 2.193l.74.739 1.09-1.09-.739-.74z"/><path d="M2.453 1.072L1.072 2.453l.683.973 1.671-1.671-.973-.683zm7.695 7.985c-.081.081-.197.094-.261.031-.063-.064-.049-.18.031-.26.08-.081.196-.094.26-.031.063.064.05.18-.03.26zm-1.091 1.091c-.08.08-.196.093-.26.03-.063-.064-.05-.18.031-.26.08-.08.196-.094.26-.031.063.064.05.18-.031.261z"/><path d="M9.057 10.148l1.091-1.091-.74-.739-1.09 1.09.739.74z"/><path d="M8.797 10.178l1.381-1.381-.683-.973-1.671 1.671.973.683zM0 6.396c0-.114.073-.206.162-.206.09 0 .163.092.163.206 0 .113-.073.205-.163.205-.089 0-.162-.092-.162-.205zm0-1.542c0-.113.073-.205.162-.205.09 0 .163.092.163.205 0 .114-.073.206-.163.206C.073 5.06 0 4.968 0 4.854z"/><path d="M0 4.854v1.542h1.046V4.854H0z"/><path d="M.162 4.649v1.952l1.172.206V4.443l-1.172.206zm11.088.205c0 .114-.073.206-.162.206-.09 0-.163-.092-.163-.206 0-.113.073-.205.163-.205.089 0 .162.092.162.205zm0 1.542c0 .113-.073.205-.162.205-.09 0-.163-.092-.163-.205 0-.114.073-.206.163-.206.089 0 .162.092.162.206z"/><path d="M11.25 6.396V4.854h-1.046v1.542h1.046z"/><path d="M11.088 6.601V4.649l-1.172-.206v2.364l1.172-.206zm-8.895 3.547c-.081-.081-.094-.197-.031-.261.064-.063.18-.049.26.031.081.08.094.196.031.26-.064.063-.18.05-.26-.03zM1.102 9.057c-.08-.08-.093-.196-.03-.26.064-.063.18-.05.26.031.08.08.094.196.031.26-.064.063-.18.05-.261-.031z"/><path d="M1.102 9.057l1.091 1.091.739-.74-1.09-1.09-.74.739z"/><path d="M1.072 8.797l1.381 1.381.973-.683-1.671-1.671-.683.973zm7.985-7.695c.081.081.094.197.031.261-.064.063-.18.049-.26-.031-.081-.08-.094-.196-.031-.26.064-.063.18-.05.26.03zm1.091 1.091c.08.08.093.196.03.26-.064.063-.18.05-.26-.031-.08-.08-.094-.196-.031-.26.064-.063.18-.05.261.031z"/><path d="M10.148 2.193L9.057 1.102l-.739.74 1.09 1.09.74-.739z"/><path d="M10.178 2.453L8.797 1.072l-.973.683 1.671 1.671.683-.973z"/></g></svg><strong>Administrator</strong>
                                            <ul>
                                                <li><a href="{{ url('/') }}/categories">Categories</a></li>
                                                <li><a href="{{ url('/') }}/skills">Skills</a></li>
                                                <li><a href="{{ url('/') }}/group-tags">Group tags</a></li>
                                                <li><a href="{{ url('/') }}/users">Users</a></li>
                                                <li><a href="{{ url('/') }}/roles">Roles</a></li>
                                            </ul>
                                        </li>
                                        <li><strong><svg width="19" height="13" viewBox="0 0 15 11" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M1.598 7.937a1.053 1.053 0 1 1 .438 2.06 1.053 1.053 0 0 1-.438-2.06zm2.403-4.869a1.224 1.224 0 0 1 .509 2.393 1.223 1.223 0 1 1-.509-2.393z"/><path d="M4.51 5.461L3.133 3.777.865 8.514l1.902.909L4.51 5.461z"/><path d="M3.991 5.241l1.249-1.68 3.131 3.637-.926 1.966-3.454-3.923z"/><path d="M9.389 9.035l2.77-5.008-1.611-1.054-2.578 4.47 1.419 1.592z"/><path d="M13.393.265l-.351-.188-4.009 2.706.024.394 4.001 2.159.335-.22V.265z"/><circle cx="8.371" cy="8.4" r="1.202"/><path d="M9.12 2.748a.229.229 0 1 1-.185.265.229.229 0 0 1 .185-.265zM13.124.04a.23.23 0 0 1 .08.451.23.23 0 0 1-.265-.186.228.228 0 0 1 .185-.265zm.001 4.868a.229.229 0 1 1 .08.45.229.229 0 0 1-.08-.45z"/></g></svg> @lang('general.reporting')</strong>
                                            <ul>
                                                <li><a href="{{ url('/') }}/time-reporting">@lang('general.time_reporting')</a></li>
                                                <li><a href="{{ url('/') }}/party-reporting">@lang('general.party_reporting')</a></li>
                                            </ul>
                                        </li>
                                        <li><strong><svg width="15" height="13" viewBox="0 0 12 10" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><g fill="#0394a6"><path d="M11.25 6.245H.002v2.25s-.038.75.208 1.066c.242.311.997.269.997.269l8.953-.011s.565.039.843-.26c.259-.278.247-.929.247-.929V6.245zm0-.625H6.887V4.618H4.365V5.62H.002V1.946s.008-.267.105-.386c.098-.12.335-.14.335-.14l10.29-.004s.237-.027.385.1c.133.114.133.43.133.43V5.62z"/><path d="M7.592 0v1.946H3.66V0h3.932zm-.705.666H4.365v.75h2.522v-.75z"/></g></svg> @lang('general.general')</strong>
                                            <ul>
                                                <li><a href="{{ url('/') }}/profile/{{ Auth::id() }}">@lang('general.profile')</a></li>
                                                <li><a href="{{ url('/') }}/change-password">@lang('auth.change_password')</a></li>
                                                <li><a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();">@lang('general.logout')</a></li>
                                            </ul>
                                        </li>
                                    </ul>

                                </div>
                            </li>

                    </ul>
                </div>

                <div class="collapse navbar-start navbar-dropdown" id="startMenu">

                    <ul>
                        <li>
                            <strong><svg width="11" height="14" viewBox="0 0 9 11" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M8.55 0H0v10.687l4.253-3.689 4.297 3.689V0z" fill="#0394a6"/></svg> Our group</strong>
                            <ul>
                                <li><a href="{{ url('/') }}/fixometer/">Fixometer</a></li>
                                <li><a href="{{ url('/') }}/community/">Community</a></li>
                                <li><a href="{{ url('/') }}/wiki/Main_Page">Restart Wiki</a></li>
                                <li><a href="{{ url('/') }}/repairdirectory/">The Repair Directory</a></li>
                            </ul>
                        </li>

                        <li>
                            <strong><svg width="15" height="15" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><path d="M5.625 0a5.627 5.627 0 0 1 5.625 5.625 5.627 5.627 0 0 1-5.625 5.625A5.627 5.627 0 0 1 0 5.625 5.627 5.627 0 0 1 5.625 0zm1.19 9.35l.104-.796c-.111-.131-.301-.249-.57-.352V4.073a9.365 9.365 0 0 0-.838-.031c-.331 0-.69.045-1.076.134l-.104.797c.138.152.328.269.57.352v2.877c-.283.103-.473.221-.57.352l.104.796h2.38zM5.604 3.462c-.572 0-.859-.26-.859-.781s.288-.781.864-.781c.577 0 .865.26.865.781s-.29.781-.87.781z" fill="#0394a6"/></svg> Other</strong>
                            <ul>
                                <li><a href="{{ url('/') }}">The Restart Project</a></li>
                                <li><a href="{{ url('/') }}/contact/">Help</a></li>
                                <li><a role="button"  data-toggle="modal" href="#onboarding" data-target="#onboarding">Welcome</a></li>
                            </ul>
                        </li>

                    </ul>

                </div>

            </div>
        </nav>

        <aside id="notifications" class="notifications collapse">
            <div class="notifications__scroll">
                <div id="tabs" class="notifications__inner">

                    <div class="alert alert-secondary" role="alert">
                        <h3>@lang('general.alert_uptodate')</h3>
                        <p>@lang('general.alert_uptodate_text')</p>
                    </div>

                    <div class="cards">

                        <div class="card card__parties">
                            <div class="card-body">
                                <h5 class="card-title"><a href="">Restart HQ</a> needs event <a href="">London School of Economics</a> approved</h5>
                                <time>Tues, 15th May 2018</time>
                            </div>
                        </div>

                        <div class="card card__devices">
                            <div class="card-body">
                                <h5 class="card-title"><a href="">Restart HQ</a> needs event <a href="">London School of Economics</a> approved</h5>
                                <time>Tues, 15th May 2018</time>
                            </div>
                        </div>

                        <div class="card card__groups">
                            <div class="card-body">
                                <h5 class="card-title"><a href="">Restart HQ</a> needs event <a href="">London School of Economics</a> approved</h5>
                                <time>Tues, 15th May 2018</time>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </aside>
