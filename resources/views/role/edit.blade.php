@extends('layouts.app')
@section('content')
<div class="container">
    <div class="row">
      <div class="col">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">FIXOMETER</a></li>
            <li class="breadcrumb-item"><a href="{{ url('/role') }}">ROLES</a></li>
            <li class="breadcrumb-item active" aria-current="page">EDIT ROLE</li>
          </ol>
        </nav>
      </div>
    </div>
    <div class="row">
      <div class="col-lg-5">
        <div class="edit-panel">
          <h4>Edit role</h4>
          <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec sed odio dui.</p>
          @if(isset($response))
            @php( App\Helpers\Fixometer::printResponse($response) )
          @endif
          <form action="/role/edit/<?php echo $formId; ?>" method="post">
            @csrf
            <input name="formId" value="<?php echo env('APP_NAME') . '_' . $formId; ?>" type="hidden">
            <div class="form-row">
              <div class="form-group col">
                <label for="inputName">Name:</label>
                <input type="text" class="form-control" id="inputName" name="inputName" value="{{ $role_name }}" disabled>
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <label for="inputRole[]">Permissions:</label>

                @foreach($permissions as $p)
                <div class="form-check">
                  <input
                    class="form-check-input"
                    type="checkbox"
                    value="<?php echo $p->idpermissions; ?>"
                    name="permissions[<?php echo $p->idpermissions; ?>]"
                    <?php echo (in_array($p->idpermissions, $activePermissions) ? ' checked' : '' ); ?>
                    >
                  <label class="form-check-label" for="permissions[<?php echo $p->idpermissions; ?>]"> <?php echo $p->permission; ?></label>
                </div>
                @endforeach
              </div>
            </div>
            <div class="form-row">
              <div class="form-group col">
                <div class="d-flex justify-content-end">
                  <button type="submit" class="btn btn-primary">Save role</button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection
