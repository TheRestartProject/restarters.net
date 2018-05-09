        @if(hasRole($user, 'Host')){

            include(ROOT . DS . 'app' . DS . 'view' . DS . 'host' . DS .'header.php');

        @else

        @if(isset($header) && $header == true)
        <nav class="navbar fixed-top">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-xs-6 col-sm-3 col-md-1">
                        <a class="brand" href="/">
                            <img class="img-responsive" alt="The Restart Project: Logo" src="/assets/images/logo_mini.png">
                        </a>
                    </div>

                    <div class="col-md-8">
                        <ul id="" class="nav nav-pills">
                            @if(hasRole($user, 'Administrator'))
                            <li role="presentation" class="dropdown">
                                    <a class="dropdown-toggle" id="user-management-dropdown" data-toggle="dropdown" href="#" role="button" aria-expanded="false">
                                        <i class="fa fa-group"></i>
                                        Users
                                    </a>
                                    <ul class="dropdown-menu" role="menu" aria-labelledby="user-management-dropdown">
                                        <li role="presentation"><a role="menuitem" tabindex="-1" href="/user/all"> User List</a></li>
                                        <li role="presentation"><a role="menuitem" tabindex="-1" href="/user/create">New User</a></li>
                                        <li role="presentation"><a role="menuitem" tabindex="-1" href="/role">Roles</a></li>
                                    </ul>

                            </li>
                            @endif

                            <li class="">

                                <a class="dropdown-toggle" id="party-management-dropdown" data-toggle="dropdown" href="#" role="button" aria-expanded="false">
                                    <i class="fa fa-recycle"></i> Parties
                                </a>

                                <ul class="dropdown-menu" role="menu" aria-labelledby="party-management-dropdown">
                                    <li role="presentation"><a role="menuitem" tabindex="-1" href="/party"> All Parties</a></li>
                                    <li role="presentation"><a role="menuitem" tabindex="-1" href="/party/create">New Party</a></li>
                                </ul>
                            </li>
                            <li class="">

                                <a  class="dropdown-toggle" id="device-management-dropdown" data-toggle="dropdown" href="#" role="button" aria-expanded="false">
                                    <i class="fa fa-wrench"></i> Devices
                                </a>
                                <ul class="dropdown-menu" role="menu" aria-labelledby="device-management-dropdown">
                                        <li role="presentation"><a role="menuitem" tabindex="-1" href="/device"> All Devices</a></li>
                                        <li role="presentation"><a role="menuitem" tabindex="-1" href="/device/create">New Device</a></li>

                                    </ul>
                            </li>
                            <li class="">

                                <a class="dropdown-toggle" id="taxonomies-dropdown" data-toggle="dropdown" href="#" role="button" aria-expanded="false">
                                   <i class="fa fa-sitemap"></i> Taxonomies
                                </a>

                                <ul class="dropdown-menu" role="menu" aria-labelledby="taxonomies-dropdown">
                                    <li role="presentation"><a role="menuitem" tabindex="-1" href="/group"> Groups</a></li>
                                    <li role="presentation"><a role="menuitem" tabindex="-1" href="/category"> Categories</a></li>

                                </ul>
                            </li>
                        </ul>
                    </div>

                    <div class="col-md-2 col-md-offset-1">

                        <div class="dropdown">

                            <button class="profile-picture dropdown-toggle" id="user-profile-dropdown" data-toggle="dropdown" aria-expanded="true">
                                @if(!empty($user->path))
                                <img src="<?php echo '/uploads/thumbnail_' . $user->path; ?>" alt="<?php echo $user->name; ?>" width="40" height="40">
                                @else
                                <?php echo $user->name; ?>
                                @endif
                                 <i class="fa fa-caret-down"></i></button>

                            <ul class="dropdown-menu" role="menu" aria-labelledby="user-profile-dropdown">

                              <li role="presentation"><a role="menuitem" tabindex="-1" href="/user/profile/<?php echo $user->id; ?>"><i class="fa fa-user"></i> My Profile</a></li>

                              <li role="presentation"><a role="menuitem" tabindex="-1" href="#">Something else here</a></li>

                              <li role="presentation"><a role="menuitem" tabindex="-1" href="/user/logout"><i class="fa fa-sign-out"></i> Logout</a></li>
                            </ul>
                          </div>

                    </div>
                </div>


        </nav>
        @endif

    @endif
