<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');

// Group the destination beds by unit so the booking chair picks a room, not a
// number. Units where somebody is already staying are worth knowing about,
// because that is how a roommate request gets honoured.
$byUnit = [];

foreach ($beds as $bed) {
    $byUnit[$bed['unit_name']]['room_type'] = $bed['room_type_name'];
    $byUnit[$bed['unit_name']]['beds'][]    = $bed;
}
?>
<div class="admin-head">
  <div>
    <h1><?= e((string) ($booking['guest_name'] ?: 'Unnamed guest')) ?></h1>
    <p>
      <?= e((string) $booking['unit_name']) ?> · <?= e((string) $booking['bed_label']) ?> ·
      <?= e(za_date((string) $booking['night'], 'D j M Y')) ?> ·
      <?= e((string) $booking['room_type_name']) ?>
    </p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <?php if ($booking['order_id']): ?>
      <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/orders/' . $booking['order_id'])) ?>">Order <?= e((string) $booking['order_reference']) ?></a>
    <?php endif; ?>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings/operations')) ?>">Back to operations</a>
  </div>
</div>

<div class="admin-grid admin-grid--sidebar">
  <div class="stack-m">
    <div class="admin-panel">
      <h2>Move to another bed</h2>
      <div class="admin-note">
        Only beds that are genuinely free on <?= e(za_date((string) $booking['night'], 'D j M')) ?> are listed — nothing booked,
        nothing sitting in somebody's cart. The booking keeps its reference and its price; only the bed changes.
        This moves <strong>this night only</strong>. A guest staying three nights has three bookings, so move each one.
      </div>

      <?php if ($beds === []): ?>
        <p class="muted">Every other bed is taken on this night. Free one up first, or cancel a booking that is no longer needed.</p>
      <?php else: ?>
        <form method="post" action="<?= e(url('/admin/bookings/' . $booking['id'] . '/move')) ?>"
              data-confirm="Move <?= e((string) ($booking['guest_name'] ?: 'this guest')) ?> to the selected bed?">
          <?= csrf_field() ?>
          <div class="field">
            <label class="field__label" for="bed">Destination bed</label>
            <select id="bed" name="bed_id" required>
              <option value="">Choose a bed…</option>
              <?php foreach ($byUnit as $unitName => $unit): ?>
                <optgroup label="<?= e((string) $unitName) ?> — <?= e((string) $unit['room_type']) ?>">
                  <?php foreach ($unit['beds'] as $bed): ?>
                    <option value="<?= (int) $bed['id'] ?>"><?= e((string) $unitName) ?> · <?= e((string) $bed['label']) ?></option>
                  <?php endforeach; ?>
                </optgroup>
              <?php endforeach; ?>
            </select>
            <p class="field__hint"><?= count($beds) ?> bed<?= count($beds) === 1 ? '' : 's' ?> free on this night.</p>
          </div>
          <button class="btn" type="submit">Move the guest</button>
        </form>
      <?php endif; ?>
    </div>

    <div class="admin-panel">
      <h2>Guest details</h2>
      <p class="muted">What reception sees on the door list. A bed with no name on it cannot be checked in.</p>
      <form method="post" action="<?= e(url('/admin/bookings/' . $booking['id'] . '/guest')) ?>">
        <?= csrf_field() ?>
        <div class="field">
          <label class="field__label" for="guest_name">Guest name</label>
          <input id="guest_name" type="text" name="guest_name" value="<?= e((string) $booking['guest_name']) ?>" required>
        </div>
        <div class="admin-grid admin-grid--2">
          <div class="field">
            <label class="field__label" for="guest_email">Email</label>
            <input id="guest_email" type="email" name="guest_email" value="<?= e((string) $booking['guest_email']) ?>">
          </div>
          <div class="field">
            <label class="field__label" for="guest_phone">Phone</label>
            <input id="guest_phone" type="text" name="guest_phone" value="<?= e((string) $booking['guest_phone']) ?>">
          </div>
        </div>
        <div class="field">
          <label class="field__label" for="roommate_request">Roommate request</label>
          <input id="roommate_request" type="text" name="roommate_request" value="<?= e((string) $booking['roommate_request']) ?>">
          <p class="field__hint">The name they asked to share with. The operations screen checks whether it actually happened.</p>
        </div>
        <div class="field">
          <label class="field__label" for="accessibility_needs">Accessibility needs</label>
          <textarea id="accessibility_needs" name="accessibility_needs" rows="2"><?= e((string) $booking['accessibility_needs']) ?></textarea>
        </div>
        <div class="field">
          <label class="field__label" for="notes">Notes</label>
          <textarea id="notes" name="notes" rows="2"><?= e((string) $booking['notes']) ?></textarea>
        </div>
        <button class="btn btn--ghost" type="submit">Save guest details</button>
      </form>
    </div>
  </div>

  <aside class="stack-m">
    <div class="admin-panel">
      <h2>This booking</h2>
      <table class="ledger">
        <tbody>
          <tr><td>Reference</td><td class="numeric"><code><?= e((string) $booking['reference']) ?></code></td></tr>
          <tr><td>Night</td><td class="numeric"><?= e(za_date((string) $booking['night'], 'D j M Y')) ?></td></tr>
          <tr><td>Room type</td><td class="numeric"><?= e((string) $booking['room_type_name']) ?></td></tr>
          <tr><td>Unit</td><td class="numeric"><?= e((string) $booking['unit_name']) ?></td></tr>
          <tr><td>Bed</td><td class="numeric"><?= e((string) $booking['bed_label']) ?></td></tr>
          <tr><td>Price</td><td class="numeric money"><?= e(money((int) $booking['price_cents'])) ?></td></tr>
          <tr><td>Status</td><td class="numeric"><span class="badge <?= $booking['status'] === 'confirmed' || $booking['status'] === 'checked_in' ? 'badge--success' : 'badge--warning' ?>"><?= e((string) $booking['status']) ?></span></td></tr>
          <?php if ($booking['order_email']): ?>
            <tr><td>Booked by</td><td class="numeric"><?= e((string) $booking['order_email']) ?></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="admin-panel">
      <h2>Sharing this room</h2>
      <?php if ($sharing === []): ?>
        <p class="muted">Nobody else is in this room on this night. The other bed is still on sale.</p>
      <?php else: ?>
        <ul class="plain-list">
          <?php foreach ($sharing as $other): ?>
            <li>
              <strong><?= e((string) ($other['guest_name'] ?: 'Unnamed guest')) ?></strong>
              <span class="muted"><?= e((string) $other['bed_label']) ?></span>
              <?php if ($other['guest_email']): ?><br><span class="muted"><?= e((string) $other['guest_email']) ?></span><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if ($booking['roommate_request']): ?>
        <p class="field__hint" style="margin-top:.75rem">
          This guest asked to share with <strong><?= e((string) $booking['roommate_request']) ?></strong>.
        </p>
      <?php endif; ?>
    </div>
  </aside>
</div>
<?php View::stop(); ?>
