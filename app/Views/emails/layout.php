<?php /** @var string $emailBody */
use App\Services\SettingsService;
$event = config('event');
?><!DOCTYPE html>
<html lang="en-ZA">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($event['title']) ?></title></head>
<body style="margin:0;padding:0;background:#F3E8D3;font-family:Helvetica,Arial,sans-serif;color:#111815;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3E8D3;padding:24px 12px;">
  <tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:16px;overflow:hidden;box-shadow:0 6px 24px rgba(14,36,28,.12);">
      <tr>
        <td style="background:#173D2F;padding:28px 32px;">
          <div style="font-family:Georgia,serif;font-size:24px;font-weight:bold;color:#FFF6E7;letter-spacing:1px;">SARCNA 2027</div>
          <div style="font-size:12px;letter-spacing:3px;text-transform:uppercase;color:#8DA98F;margin-top:4px;">Cape Winelands Convention</div>
          <div style="font-family:Georgia,serif;font-size:15px;color:#D9A441;margin-top:12px;font-style:italic;"><?= e($event['slogan']) ?></div>
        </td>
      </tr>
      <tr><td style="padding:32px;font-size:15px;line-height:1.65;color:#2b3a33;"><?= $emailBody ?></td></tr>
      <tr>
        <td style="background:#0E241C;padding:24px 32px;color:rgba(255,246,231,.75);font-size:12px;line-height:1.6;">
          <p style="margin:0 0 8px;color:#FFF6E7;font-weight:bold;"><?= e($event['dates_label']) ?> &middot; <?= e($event['venue_name']) ?></p>
          <p style="margin:0 0 8px;"><?= e($event['venue_region']) ?></p>
          <p style="margin:0;">
            <a href="<?= e(url('/')) ?>" style="color:#D9A441;">sarcna.org.za</a> &nbsp;&middot;&nbsp;
            <a href="mailto:<?= e(SettingsService::get('contact_email', config('contact.email'))) ?>" style="color:#D9A441;"><?= e(SettingsService::get('contact_email', config('contact.email'))) ?></a>
          </p>
          <p style="margin:12px 0 0;font-size:11px;opacity:.7;">You are receiving this message because you registered, booked or enquired on the SARCNA 2027 Convention website.</p>
        </td>
      </tr>
    </table>
  </td></tr>
</table>
</body>
</html>
