<!-- Modal -->
<div class="modal fade" id="add" tabindex="-1" role="dialog" aria-labelledby="addNewUser" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addNewUser">Add new user</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          @include('partials/cross')
        </button>
      </div>
      <div class="modal-body">
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
