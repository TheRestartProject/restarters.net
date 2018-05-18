@include('layouts.header')
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light navbar-laravel">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <svg width="152" height="38" viewBox="0 0 115 29" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" stroke-linejoin="round" stroke-miterlimit="1.414"><title>{{ config('app.name', 'Laravel') }}</title><path d="M32.451 20.338V7.98h6.905v1.311h-5.152v4.141h4.326v1.295h-4.326v5.611h-1.753zm9.218 0V11.27h1.645v9.068h-1.645zm0-10.713V7.98h1.645v1.645h-1.645zm4.033 10.713l3.449-4.684-3.348-4.384h1.953l2.647 3.49 2.397-3.49h1.603l-3.14 4.601 3.416 4.467h-1.954l-2.731-3.591-2.638 3.591h-1.654zm14.947.209c-1.297 0-2.333-.43-3.106-1.29-.774-.86-1.161-2.011-1.161-3.453 0-1.459.388-2.612 1.165-3.461.776-.849 1.83-1.274 3.16-1.274 1.331 0 2.384.425 3.161 1.274.776.849 1.165 1.997 1.165 3.444 0 1.481-.39 2.644-1.169 3.49-.78.847-1.851 1.27-3.215 1.27zm.025-1.236c1.742 0 2.613-1.175 2.613-3.524 0-2.321-.86-3.482-2.58-3.482-1.714 0-2.572 1.166-2.572 3.499 0 2.338.847 3.507 2.539 3.507zm6.93 1.027V11.27h1.645v1.703c.802-1.269 1.832-1.904 3.09-1.904 1.213 0 2.04.635 2.48 1.904.779-1.275 1.792-1.912 3.039-1.912.802 0 1.422.235 1.862.706.44.47.66 1.128.66 1.974v6.597h-1.653V14c0-1.035-.41-1.553-1.228-1.553-.852 0-1.745.604-2.68 1.812v6.079h-1.654V14c0-1.041-.417-1.561-1.252-1.561-.829 0-1.717.607-2.664 1.82v6.079h-1.645zm22.612-.292c-1.102.334-2.046.501-2.83.501-1.336 0-2.426-.444-3.269-1.332-.844-.888-1.266-2.039-1.266-3.453 0-1.375.372-2.502 1.115-3.382.743-.879 1.694-1.319 2.852-1.319 1.096 0 1.944.39 2.542 1.169.599.779.898 1.887.898 3.323l-.008.51h-5.72c.239 2.154 1.294 3.231 3.164 3.231.685 0 1.526-.184 2.522-.551v1.303zm-5.611-5.219h4c0-1.687-.629-2.53-1.887-2.53-1.264 0-1.968.843-2.113 2.53zm11.214 5.72c-.835 0-1.486-.24-1.954-.718-.467-.479-.701-1.144-.701-1.996v-5.327h-1.136V11.27h1.136V9.625l1.645-.159v1.804h2.371v1.236h-2.371v5.026c0 1.186.512 1.779 1.536 1.779.217 0 .482-.036.793-.109v1.136c-.506.139-.946.209-1.319.209zm10.321-.501c-1.103.334-2.046.501-2.831.501-1.336 0-2.426-.444-3.269-1.332-.843-.888-1.265-2.039-1.265-3.453 0-1.375.372-2.502 1.115-3.382.743-.879 1.693-1.319 2.851-1.319 1.097 0 1.944.39 2.543 1.169.598.779.897 1.887.897 3.323l-.008.51h-5.72c.24 2.154 1.295 3.231 3.165 3.231.685 0 1.525-.184 2.522-.551v1.303zm-5.612-5.219h4c0-1.687-.629-2.53-1.887-2.53-1.264 0-1.968.843-2.113 2.53zm8.492 5.511V11.27h1.645v1.703c.652-1.269 1.598-1.904 2.839-1.904.167 0 .343.014.526.042v1.537a2.406 2.406 0 0 0-.751-.142c-1.041 0-1.912.617-2.614 1.853v5.979h-1.645zM18.543 6.445a1.434 1.434 0 1 0-1.564 2.404 9.113 9.113 0 0 1 4.162 7.661c0 3.1-1.555 5.962-4.158 7.659l-.004.002a9.106 9.106 0 0 1-4.974 1.475 9.107 9.107 0 0 1-4.975-1.475l-.002-.001a9.115 9.115 0 0 1-4.16-7.66A9.113 9.113 0 0 1 7.03 8.849a1.435 1.435 0 0 0-1.564-2.404A11.972 11.972 0 0 0 0 16.51a11.97 11.97 0 0 0 5.466 10.065l.003.002a11.963 11.963 0 0 0 6.536 1.936c2.328 0 4.589-.67 6.538-1.938a11.972 11.972 0 0 0 5.466-10.065c0-4.075-2.043-7.837-5.466-10.065" fill-rule="nonzero"/><path d="M15.186 4.353c.215-.733-.094-2.497-1.006-3.578-.429.614-.67 1.401-.88 2.08-.365.134-.73-.029-1.163-.167-.233-.074-1.011-.246-1.168-.497-.149-.237.145-.508.292-.79.101-.192.188-.47.272-.686.098-.255.139-.503-.013-.702-.923.068-2.289.822-2.788 1.843-.134.272-.204.8-.178 1.056.022.212.193.637.322.862.497.867 1.177 1.589 1.533 2.454.44 1.068.342 1.972.363 3.093.082 4.503-.013 5.946-.02 10.393 0 .386-.028.845.063 1.032.52 1.073 2.059 1.178 2.581-.001.173-.39.055-1.162.034-1.715-.067-1.767-.112-3.476-.16-5.14-.067-2.324-.122-1.833-.235-4.007-.057-1.077-.046-2.274.551-3.315.281-.489 1.314-1.588 1.6-2.215" fill-rule="nonzero"/></svg>
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">
                      @if (!is_null(Auth::user()) && FixometerHelper::hasRole(Auth::user(), 'Administrator'))
                        <li class="nav-item">
                          <a class="nav-link" href="/admin">
                            Dashboard
                          </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a id="userDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                Users <span class="caret"></span>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="userDropdown">
                              <a class="dropdown-item" href="/user/all">
                                  {{ __('All Users') }}
                              </a>
                              <a class="dropdown-item" href="/user/create">
                                  {{ __('Create User') }}
                              </a>
                              <a class="dropdown-item" href="/role">
                                  {{ __('Roles') }}
                              </a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a id="partyDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                Parties <span class="caret"></span>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="partyDropdown">
                              <a class="dropdown-item" href="/party">
                                  {{ __('All Parties') }}
                              </a>
                              <a class="dropdown-item" href="/party/create">
                                  {{ __('New Party') }}
                              </a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a id="deviceDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                Devices <span class="caret"></span>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="deviceDropdown">
                              <a class="dropdown-item" href="/device">
                                  {{ __('All Devices') }}
                              </a>
                              <a class="dropdown-item" href="/device/create">
                                  {{ __('New Device') }}
                              </a>
                            </div>
                        </li>
                        <li class="nav-item dropdown">
                            <a id="deviceDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                Taxonomies <span class="caret"></span>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="deviceDropdown">
                              <a class="dropdown-item" href="/group">
                                  {{ __('Groups') }}
                              </a>
                              <a class="dropdown-item" href="/category">
                                  {{ __('Categories') }}
                              </a>
                            </div>
                        </li>
                      @endif
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->
                        @guest
                            <li><a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a></li>
                            <li><a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a></li>
                        @else
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    @if (FixometerHelper::hasRole(Auth::user(), 'Administrator'))
                                      <a class="dropdown-item" href="/admin">
                                          {{ __('Dashboard') }}
                                      </a>
                                      <hr>
                                    @endif
                                    <a class="dropdown-item" href="/profile">
                                        {{ __('Profile') }}
                                    </a>
                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
</body>

@include('layouts.footer')

</html>
