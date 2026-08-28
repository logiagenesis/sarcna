<?php
use App\Services\SettingsService;

if (!SettingsService::bool('mock_data_banner', true)) {
    return;
}
?>
<div class="notice-bar" data-dismissible role="note">
  <span><strong>Committee preview.</strong> Dates, venue, pricing and imagery are placeholder content for review — imagery is original illustration, not photographs of the venue.</span>
  <button type="button" data-dismiss aria-label="Dismiss this notice">&times;</button>
</div>
