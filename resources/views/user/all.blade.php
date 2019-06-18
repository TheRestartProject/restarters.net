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
                <th><a href="/user/all/search?{{ FixometerHelper::buildSortQuery('users.name') }}">Name</a></th>
                <th>Email address</th>
                <th><a href="/user/all/search?{{ FixometerHelper::buildSortQuery('users.role') }}">Role</a></th>
                <th><a href="/user/all/search?{{ FixometerHelper::buildSortQuery('users.location') }}">Location</a></th>
                <th><a href="/user/all/search?{{ FixometerHelper::buildSortQuery('users.country') }}">Country</a></th>
                <th>Groups</th>
                <th width="90"><a href="/user/all/search?{{ FixometerHelper::buildSortQuery('users.created_at') }}">Joined</a></th>
                <th width="90"><a href="/user/all/search?{{ FixometerHelper::buildSortQuery('users.updated_at') }}">Last login</a></th>
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
                      <td>

                          @if(FixometerHelper::hasRole($user, 'Administrator'))
                          <a href="/user/edit/<?php echo $u->id; ?>"><?php echo $u->name; ?></a>
                          @else
                          <?php echo $u->name; ?>
                          @endif

                      </td>
                      <td>
                        <span class="js-copy hover-pointer popover-usergroups"
                        data-toggle="popover"
                        data-trigger="hover"
                        data-placement="top"
                        data-html="true"
                        data-original-email="{{ $u->email }}"
                        data-copy="{{ $u->email }}"
                        data-content="{{ $u->email }} </br> <b>Click/press to copy</b>">
                          {{ str_limit($u->email, 15) }}
                        </span>
                      </td>
                      <td>
                        @if ($u->role == 'Administrator')
                          Admin
                        @else
                          {{ $u->role }}
                        @endif
                      </td>
                      <td>
                        @if (!empty($u->location))
                          <?php echo $u->location; ?>
                        @else
                          N/A
                        @endif
                      </td>
                      <td>{{ $u->country }}</td>
                      <td class="text-center">
                        @if (isset($u->groups) && $u->groups->count() > 0)
                            <span class="popover-usergroups" data-toggle="popover" data-html="true" data-content="@include('partials.usergroups-popover')">{{ $u->groups->count() }}</span>
                        @else
                          0
                        @endif
                      </td>

                      <td>
                        <span title="{{ $u->created_at }}">{{ !is_null($u->created_at) ? $u->created_at->diffForHumans(null, true) : 'Never' }}</span>
                      </td>

                      <td>
                        <span title="{{ $u->lastLogin }}">{{ !is_null($u->lastLogin) ? $u->lastLogin->diffForHumans(null, true) : 'Never' }}</span>
                      </td>
                  </tr>
                @endif
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-center">
          <nav aria-label="Pagination">
            @if (!empty($_GET) || isset($name))
              {!! $userlist->appends(Request::except('page'))->links() !!} <!-- 'selected_country' => $selected_country -->
            @else
              {!! $userlist->links() !!}
            @endif
          </nav>
        </div>

        <div class="d-flex justify-content-center">
            Showing {{ $userlist->firstItem() }} to {{ $userlist->lastItem() }} of {{ $userlist->total() }} results
        </div>
        <br>
        <br>

      </div>
    </div>
  </div>

@include('includes/modals/create-user')
@endsection
