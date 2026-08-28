<?php
use App\Services\SettingsService;

if (!SettingsService::bool('whatsapp_enabled', true)) {
    return;
}

$number = preg_replace('/\D+/', '', (string) SettingsService::get('whatsapp_number', config('contact.whatsapp_number', '')));

if ($number === '' || $number === null) {
    return;
}
?>
<a class="whatsapp-fab" href="<?= e(whatsapp_link()) ?>" target="_blank" rel="noopener"
   aria-label="Chat to the SARCNA 2027 committee on WhatsApp">
  <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1-.3-.1-.5-.2-.7.1-.2.3-.7 1-.9 1.2-.2.2-.3.2-.6.1-.3-.2-1.2-.5-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6l.5-.5c.1-.2.2-.3.3-.5 0-.2 0-.4 0-.5 0-.2-.7-1.6-.9-2.2-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.2.2 2.1 3.2 5.1 4.4.7.3 1.3.5 1.7.6.7.2 1.4.2 1.9.1.6-.1 1.7-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4M12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2m0 1.8a8.2 8.2 0 0 1 6.9 12.6l-.4.6.6 2.2-2.3-.6-.5.3A8.2 8.2 0 1 1 12 3.8"/></svg>
  <span class="whatsapp-fab__label">WhatsApp us</span>
</a>
