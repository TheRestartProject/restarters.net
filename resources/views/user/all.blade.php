@extends('layouts.app')
@section('content')
  <div class="container">
    <div class="row justify-content-end">
      <div class="col">
        <div class="d-flex justify-content-between">
          <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
              <li class="breadcrumb-item"><a href="{{ url('/') }}">FIXOMETER</a></li>
              <li class="breadcrumb-item active" aria-current="page">USERS</li>
            </ol>
          </nav>
          <div class="">
            <a href="#" data-toggle="modal" data-target="#add" class="btn btn-primary">Create new user</a>
          </div>
        </div>
      </div>
    </div>
    <div class="row justify-content-center">
      <div class="col-4">
        <form class="" action="index.html" method="post">
          <div class="form-row">
            <div class="form-group col">
              <button type="submit" class="btn btn-primary btn-block">Search all users</button>
            </div>
          </div>
          <div class="block">
            <div class="form-row">
              <div class="form-group col">
                <h4>By details</h4>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="name">Name:</label>
                <input type="text" class="form-control" id="inputName" name="inputName" placeholder="Search by name">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="inputEmail">Email:</label>
                <input type="text" class="form-control" id="inputEmail" name="inputEmail" placeholder="Search by email address">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="inputTownCity">Town/City:</label>
                <input type="text" class="form-control" id="inputTownCity" name="inputTownCity" placeholder="E.g. Paris, London, Brussels">
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="inputCountry">Country:</label>
                <select class="form-control" id="inputCountry" name="inputCountry">
                  <option value="" selected>Choose country</option>
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="inputRole">Role:</label>
                <select class="form-control" id="inputRole" name="inputRole">
                  <option value="" selected>Choose role</option>
                  @foreach (FixometerHelper::allRoles() as $role)
                    <option value="{{ $role->idroles }}">{{ $role->role }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="inputPermission">Permission:</label>
                <select class="form-control" id="inputPermission" name="inputPermission">
                  <option value="" selected>Choose permission</option>
                </select>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="col-8">
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
                  <td><?php echo 'London';//echo $u->location; ?></td>
                  <td><?php echo 'Group';//echo $u->groups; ?></td>
                  <td data-value="<?php echo $u->modified_at; ?>" ><?php echo FixometerHelper::dateFormat($u->modified_at); ?></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="add" tabindex="-1" role="dialog" aria-labelledby="addNewUser" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addNewUser">Add new user</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="container-fluid">
            <form method="post">
              <div class="form-row">
                <div class="form-group col">
                  <label for="name">Name:</label>
                  <input type="text" class="form-control" id="inputName" name="inputName">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col">
                  <label for="email">Email address:</label>
                  <input type="email" class="form-control" id="inputEmail" name="inputEmail">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col">
                  <label for="inputRole">User role:</label>
                  <select class="form-control" id="inputRole" name="inputRole">
                    <option value="" selected>Choose role</option>
                    @foreach (FixometerHelper::allRoles() as $role)
                      <option value="{{ $role->idroles }}">{{ $role->role }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col">
                  <label for="inputPassword">Password:</label>
                  <input type="password" class="form-control" id="inputPassword" name="inputPassword">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col">
                  <label for="inputPasswordRepeat">Repeat password:</label>
                  <input type="password" class="form-control" id="inputPasswordRepeat" name="inputPasswordRepeat">
                </div>
              </div>
              <div class="form-row">
                <div class="form-group col">
                  <div class="d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">Add new user</button>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
