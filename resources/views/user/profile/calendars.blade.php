<?php
  $find_out_more_url = env('DISCOURSE_URL') . '/session/sso?return_path=' . env('DISCOURSE_URL') . __('general.calendar_feed_help_url');
?>
<div class="vue">
  <CalendarsTab find-out-more-url="{{ $find_out_more_url }}" />
</div>
