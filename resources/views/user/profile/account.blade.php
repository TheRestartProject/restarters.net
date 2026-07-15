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

<div class="vue">
  <DeleteAccountTab />
</div>
