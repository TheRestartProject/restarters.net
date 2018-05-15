@include('layouts.header')
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light navbar-laravel">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
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
