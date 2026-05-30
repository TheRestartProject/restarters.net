<?php
  $platform_preferences_url = env('PLATFORM_COMMUNITY_URL') . '/u/' . Auth::user()->username . '/preferences/emails';
?>
<div class="vue">
  <EmailPreferencesTab
    :initial-invites="{{ $user->invites ? 'true' : 'false' }}"
    platform-preferences-url="{{ $platform_preferences_url }}"
  />
</div>
