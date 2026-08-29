<?php
use App\Services\PhotoService;
use App\Services\SettingsService;

if (!SettingsService::bool('mock_data_banner', true)) {
    return;
}

// Only claim the imagery is illustration while that is actually true.
$illustrations = PhotoService::stillShowingIllustrations();
?>
<div class="notice-bar" data-dismissible role="note">
  <span><strong>Committee preview.</strong> Dates, venue and pricing are placeholder content for review<?= $illustrations ? ' — some imagery is still original illustration rather than photographs of the venue' : '' ?>.</span>
  <button type="button" data-dismiss aria-label="Dismiss this notice">&times;</button>
</div>
