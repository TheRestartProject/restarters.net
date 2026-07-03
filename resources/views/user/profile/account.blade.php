<div class="vue">
  <PasswordTab />
</div>

<div class="vue">
  <LanguageTab />
</div>

@if (App\Helpers\Fixometer::hasRole(Auth::user(), 'Administrator'))
<div class="vue">
  <AdminSettingsTab :user-id="{{ (int) $user->id }}" />
</div>
@endif

<form action="/user/soft-delete" method="post" id="delete-form">
    @csrf

    {{ Form::hidden('id', $user->id) }}

    <div class="alert alert-danger" role="alert">
    <div class="row">
        <div class="col-md-8 d-flex flex-column align-content-center">@lang('auth.delete_account_text')</div>
        <div class="col-md-4 d-flex flex-column align-content-center"><button type="submit" class="btn btn-danger" id="delete-form-submit">
    @lang('auth.delete_account')</div>
    </div>


    </button>
    </div>

</form>
