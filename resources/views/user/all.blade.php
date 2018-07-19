@extends('layouts.app')
@section('content')
  <div class="container">
    <div class="row justify-content-end">
      <div class="col">
        <div class="d-md-flex justify-content-between">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{{ route('dashboard') }}}">FIXOMETER</a></li>
              <li class="breadcrumb-item active" aria-current="page">USERS</li>
            </ol>
          </nav>
          <div class="button-group-filters">
            <button class="reveal-filters btn btn-secondary d-md-none d-lg-none d-xl-none" type="button" data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter">Reveal filters</button>
            <a href="#" data-toggle="modal" data-target="#add" class="btn btn-primary">Create new user</a>
          </div>
        </div>
      </div>
    </div>

    @if(isset($response))
      @php( FixometerHelper::printResponse($response) )
    @endif

    @if (\Session::has('success'))
        <div class="alert alert-success">
            {!! \Session::get('success') !!}
        </div>
    @endif

    @if (\Session::has('danger'))
        <div class="alert alert-danger">
            {!! \Session::get('danger') !!}
        </div>
    @endif

    <div class="row justify-content-center">
      <div class="col-md-4 col-lg-3">
        <aside class="collapse d-md-block d-lg-block d-xl-block fixed-overlay" id="collapseFilter">
        <form class="" action="/user/all/search" method="get">
          <div class="form-row">
            <div class="form-group col mobile-search-bar">
              <button type="submit" class="btn btn-primary btn-block">Search all users</button>
              <button type="button" class="d--lg-none d-xl-none d-md-none mobile-search-bar__close" data-toggle="collapse" data-target="#collapseFilter" aria-expanded="false" aria-controls="collapseFilter"><svg width="21" height="21" viewBox="0 0 12 12" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linejoin:round;stroke-miterlimit:1.41421;"><title>Close</title><g><path d="M11.25,10.387l-10.387,-10.387l-0.863,0.863l10.387,10.387l0.863,-0.863Z"/><path d="M0.863,11.25l10.387,-10.387l-0.863,-0.863l-10.387,10.387l0.863,0.863Z"/></g></svg></button>
            </div>
          </div>
          <div class="block">
                <h4>By details</h4>
            <div class="form-row">
              <div class="form-group col">
                <label for="name">Name:</label>
                @if (isset($name))
                  <input type="text" class="form-control" id="inputName" name="name" placeholder="Search by name" value="{{ $name }}">
                @else
                  <input type="text" class="form-control" id="inputName" name="name" placeholder="Search by name">
                @endif
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="inputEmail">Email:</label>
                @if (isset($email))
                  <input type="text" class="form-control" id="inputEmail" name="email" placeholder="Search by email address" value="{{ $email }}">
                @else
                  <input type="text" class="form-control" id="inputEmail" name="email" placeholder="Search by email address">
                @endif
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="inputTownCity">Town/City:</label>
                @if (isset($location))
                  <input type="text" class="form-control" id="inputTownCity" name="location" placeholder="E.g. Paris, London, Brussels" value="{{ $location }}">
                @else
                  <input type="text" class="form-control" id="inputTownCity" name="location" placeholder="E.g. Paris, London, Brussels">
                @endif
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="inputCountry">Country:</label>
                <div class="form-control form-control__select">
                    <select id="country" name="country" class="field select2">
                        <option value=""></option>
                        @foreach (FixometerHelper::getAllCountries() as $country_code => $country_name)
                          @if (isset($country) && $country_code == $country)
                            <option value="{{ $country_code }}" selected>{{ $country_name }}</option>
                          @else
                            <option value="{{ $country_code }}">{{ $country_name }}</option>
                          @endif
                        @endforeach
                    </select>
                </div>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="role">Role:</label>
                <select class="form-control" id="inputRole" name="role">
                  <option value="" selected>Choose role</option>
                  @foreach (FixometerHelper::allRoles() as $r)
                    @if (isset($role) && $r->idroles == $role)
                      <option value="{{ $r->idroles }}" selected>{{ $r->role }}</option>
                    @else
                      <option value="{{ $r->idroles }}">{{ $r->role }}</option>
                    @endif
                  @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="permission">Permission:</label>
                <div class="form-control form-control__select">
                <select id="permissions" name="permissions[]" class="form-control select2-tags" multiple data-live-search="true" title="Choose permissions...">
                      @foreach (FixometerHelper::allPermissions() as $p)
                        @if (isset($permissions) && in_array($p->idpermissions, $permissions))
                          <option value="{{ $p->idpermissions }}" selected>{{ $p->permission }}</option>
                        @else
                          <option value="{{ $p->idpermissions }}">{{ $p->permission }}</option>
                        @endif
                      @endforeach
                    </select>
                </div>
              </div>
            </div>
          </div>
        </form>
        </aside>
      </div>

      <div class="col-md-8 col-lg-9">
        <div class="table-responsive">
          <table class="table table-striped">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email address</th>
                <th>Role</th>
                <th>Location</th>
                <th>Groups</th>
                <th>Last login</th>
              </tr>
            </thead>
            <tbody>
              @foreach($userlist as $u)

                @php( $display = true )

                <?php
                  if (isset($permissions)) {
                    foreach($permissions as $p) {
                      $user_permissions = array_column($u->permissions, 'idpermissions');
                      if(!in_array($p, $user_permissions)) {
                        $display = false;
                        break;
                      }
                    }
                  }
                ?>

                @if ($display)
                  <tr>
                      <!-- <td><?php //echo $u->id; ?></td> -->
                      <td>

                          @if(FixometerHelper::hasRole($user, 'Administrator'))
                          <a href="/user/edit/<?php echo $u->id; ?>"><?php echo $u->name; ?></a>
                          @else
                          <?php echo $u->name; ?>
                          @endif

                      </td>
                      <td><?php echo $u->email; ?></td>
                      <td><?php echo $u->role; ?></td>
                      <td>
                        @if (!empty($u->location))
                          <?php echo $u->location; ?>
                        @else
                          N/A
                        @endif
                      </td>
                      <td>
                        @if (isset($u->groups))
                          @php( $set_groups = false )

                          @foreach($u->groups as $n => $g)
                            @if ($n == count($u->groups) - 1)
                              <a href="/group/view/{{ $g }}">{{ $g }}</a>
                              @php( $set_groups = true )
                            @else
                              <a href="/group/view/{{ $g }}">{{ $g }}</a>,
                              @php( $set_groups = true )
                            @endif
                          @endforeach

                          @if(!$set_groups)
                            N/A
                          @endif
                        @else
                          N/A
                        @endif
                      </td>
                      <td data-value="<?php //echo $u->modified_at; ?>" ><?php //echo FixometerHelper::dateFormat($u->modified_at); ?></td>
                  </tr>
                @endif
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-center">
          <nav aria-label="Page navigation example">
            @if (!empty($_GET) || isset($name))
              {!! $userlist->appends(['name' => $name, 'email' => $email, 'location' => $location, 'country' => $country, 'role' => $role, 'permissions' => $permissions ])->links() !!} <!-- 'selected_country' => $selected_country -->
            @else
              {!! $userlist->links() !!}
            @endif
          </nav>
        </div>
        <br>
        <br>

      </div>
    </div>
  </div>

@include('includes/modals/create-user')
@endsection
