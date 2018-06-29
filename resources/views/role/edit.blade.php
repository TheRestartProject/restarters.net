@extends('layouts.app')
@section('content')
  <div class="container">
    <div class="row">
      <div class="col">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{{ route('dashboard') }}}">FIXOMETER</a></li>
            <li class="breadcrumb-item"><a href="{{ url('/role') }}">ROLES</a></li>
            <li class="breadcrumb-item active" aria-current="page">EDIT ROLE</li>
          </ol>
        </nav>
      </div>
    </div>
    <div class="row">
      <div class="col-5">
        <h4>{!! $title !!}</h4>
        @if(isset($response))
          @php( FixometerHelper::printResponse($response) )
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
              @foreach($permissions as $p)
              <div class="checkbox">
                  <label>
                    <input
                      type="checkbox"
                      value="<?php echo $p->idpermissions; ?>"
                      name="permissions[<?php echo $p->idpermissions; ?>]"
                      <?php echo (in_array($p->idpermissions, $activePermissions) ? ' checked' : '' ); ?>
                      > <?php echo $p->permission; ?>
                  </label>
              </div>
              @endforeach
            </div>
          </div>
          <div class="form-row">
            <div class="form-group col">
              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> save</button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection
