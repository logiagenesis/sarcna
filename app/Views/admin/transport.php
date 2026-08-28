<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Transport</h1><p><?= (int) $summary['taken'] ?> of <?= (int) $summary['capacity'] ?> seats sold across every departure</p></div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/transport')) ?>">Export passengers</a>
</div>

<?php foreach ($routes as $route): ?>
  <div class="admin-panel">
    <div class="cluster cluster--between" style="align-items:flex-start">
      <div>
        <h2 style="margin-bottom:.25rem"><?= e($route['name']) ?>
          <?php if ((int) $route['is_active'] !== 1): ?><span class="badge badge--error">Inactive</span><?php endif; ?>
          <?php if ((int) $route['requires_flight_number'] === 1): ?><span class="badge badge--plum">Flight number</span><?php endif; ?>
        </h2>
        <p class="muted" style="font-size:var(--step--1);margin:0">
          <?= e(ucfirst(str_replace('_', ' ', (string) $route['direction']))) ?> &middot;
          <?= (int) $route['price_cents'] === 0 ? 'Free' : e(money((int) $route['price_cents'])) ?> per seat &middot;
          <?= (int) $route['slot_count'] ?> departure(s)
        </p>
      </div>
      <div class="cluster">
        <form method="post" action="<?= e(url('/admin/transport/routes/' . $route['id'] . '/delete')) ?>" data-confirm="Delete this route? Only possible when no passengers are booked.">
          <?= csrf_field() ?>
          <button class="btn btn--sm btn--ghost" type="submit">Delete</button>
        </form>
      </div>
    </div>

    <details style="margin-top:1rem">
      <summary style="cursor:pointer;font-weight:700">Edit route</summary>
      <form method="post" action="<?= e(url('/admin/transport/routes')) ?>" style="margin-top:1rem">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $route['id'] ?>">
        <div class="field-row field-row--2">
          <div class="field"><label class="field__label">Name</label><input type="text" name="name" value="<?= e($route['name']) ?>" required></div>
          <div class="field"><label class="field__label">Slug</label><input type="text" name="slug" value="<?= e($route['slug']) ?>"></div>
        </div>
        <div class="field-row field-row--3">
          <div class="field">
            <label class="field__label">Direction</label>
            <select name="direction">
              <?php foreach (['to_venue' => 'To the venue', 'from_venue' => 'From the venue', 'return' => 'Return', 'onsite' => 'On site'] as $key => $label): ?>
                <option value="<?= $key ?>" <?= $route['direction'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field"><label class="field__label">Price (R)</label><input type="number" step="0.01" min="0" name="price" value="<?= number_format(((int) $route['price_cents']) / 100, 2, '.', '') ?>"></div>
          <div class="field"><label class="field__label">Sort order</label><input type="number" name="sort_order" value="<?= (int) $route['sort_order'] ?>"></div>
        </div>
        <div class="field"><label class="field__label">Description (HTML allowed)</label><textarea name="description" rows="3"><?= e((string) $route['description']) ?></textarea></div>
        <label class="checkbox"><input type="checkbox" name="requires_flight_number" value="1" <?= (int) $route['requires_flight_number'] === 1 ? 'checked' : '' ?>><span>Ask for a flight number</span></label>
        <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= (int) $route['is_active'] === 1 ? 'checked' : '' ?>><span>On sale</span></label>
        <button class="btn btn--sm" type="submit">Save route</button>
      </form>
    </details>

    <div class="table-wrap" style="margin-top:1rem">
      <table class="admin-table">
        <thead><tr><th>Departs</th><th>Pick-up</th><th>Drop-off</th><th class="numeric">Seats</th><th>Status</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($route['slots'] as $slot):
            $left = max(0, (int) $slot['capacity'] - (int) $slot['seats_taken']); ?>
            <tr>
              <td><strong><?= e(za_date((string) $slot['departs_at'], 'D j M, H:i')) ?></strong><?php if ($slot['notes']): ?><br><span class="muted"><?= e($slot['notes']) ?></span><?php endif; ?></td>
              <td><?= e($slot['pickup_point']) ?></td>
              <td><?= e($slot['dropoff_point']) ?></td>
              <td class="numeric"><?= (int) $slot['seats_taken'] ?>/<?= (int) $slot['capacity'] ?><br><span class="muted"><?= $left ?> free</span></td>
              <td><?= (int) $slot['is_active'] === 1 ? '<span class="badge badge--success">Active</span>' : '<span class="badge">Off</span>' ?></td>
              <td>
                <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/transport/manifest/' . $slot['id'])) ?>">Manifest (<?= (int) $slot['passengers'] ?>)</a>
                <form method="post" action="<?= e(url('/admin/transport/slots/' . $slot['id'] . '/delete')) ?>" data-confirm="Delete this departure?">
                  <?= csrf_field() ?>
                  <button class="btn btn--sm btn--ghost" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if ($route['slots'] === []): ?><tr><td colspan="6" class="muted">No departures yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <details style="margin-top:1rem">
      <summary style="cursor:pointer;font-weight:700">Add a departure</summary>
      <form method="post" action="<?= e(url('/admin/transport/slots')) ?>" style="margin-top:1rem">
        <?= csrf_field() ?>
        <input type="hidden" name="route_id" value="<?= (int) $route['id'] ?>">
        <div class="field-row field-row--2">
          <div class="field"><label class="field__label">Departs at</label><input type="datetime-local" name="departs_at" required></div>
          <div class="field"><label class="field__label">Seat capacity</label><input type="number" name="capacity" value="22" min="1" max="200" required></div>
        </div>
        <div class="field-row field-row--2">
          <div class="field"><label class="field__label">Pick-up point</label><input type="text" name="pickup_point" required></div>
          <div class="field"><label class="field__label">Drop-off point</label><input type="text" name="dropoff_point" required></div>
        </div>
        <div class="field"><label class="field__label">Notes</label><input type="text" name="notes"></div>
        <label class="checkbox"><input type="checkbox" name="is_active" value="1" checked><span>On sale</span></label>
        <button class="btn btn--sm" type="submit">Add departure</button>
      </form>
    </details>
  </div>
<?php endforeach; ?>

<div class="admin-panel">
  <h2>New route</h2>
  <form method="post" action="<?= e(url('/admin/transport/routes')) ?>">
    <?= csrf_field() ?>
    <div class="field-row field-row--2">
      <div class="field"><label class="field__label">Name</label><input type="text" name="name" required placeholder="e.g. George Airport Shuttle"></div>
      <div class="field"><label class="field__label">Price (R)</label><input type="number" step="0.01" min="0" name="price" value="0" required></div>
    </div>
    <div class="field">
      <label class="field__label">Direction</label>
      <select name="direction">
        <option value="to_venue">To the venue</option>
        <option value="from_venue">From the venue</option>
        <option value="return">Return</option>
        <option value="onsite">On site</option>
      </select>
    </div>
    <div class="field"><label class="field__label">Description</label><textarea name="description" rows="3"></textarea></div>
    <label class="checkbox"><input type="checkbox" name="requires_flight_number" value="1"><span>Ask for a flight number</span></label>
    <label class="checkbox"><input type="checkbox" name="is_active" value="1" checked><span>On sale</span></label>
    <button class="btn" type="submit">Create route</button>
  </form>
</div>
<?php View::stop(); ?>
