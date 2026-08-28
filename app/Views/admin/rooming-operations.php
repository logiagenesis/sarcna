<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');

$critical = array_values(array_filter($problems, static fn (array $p): bool => $p['severity'] === 'critical'));
$warnings = array_values(array_filter($problems, static fn (array $p): bool => $p['severity'] !== 'critical'));

$unmatched = array_values(array_filter($roommates, static fn (array $r): bool => !$r['matched']));
$nights    = $byType['nights'];
?>
<div class="admin-head">
  <div>
    <h1>Rooming operations</h1>
    <p>Everything that needs a person before anyone arrives at Boschendal.</p>
  </div>
  <div style="display:flex;gap:.5rem;flex-wrap:wrap">
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings/run-sheet')) ?>">Run sheet</a>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/rooming-list')) ?>">Rooming list CSV</a>
    <a class="btn btn--sm" href="<?= e(url('/admin/bookings/board')) ?>">Bed board</a>
  </div>
</div>

<?php View::include('partials.rooming-tabs'); ?>

<div class="tiles">
  <div class="tile <?= $critical !== [] ? 'tile--error' : 'tile--success' ?>">
    <div class="tile__label">Needs you now</div>
    <div class="tile__value"><?= count($critical) ?></div>
    <div class="tile__meta"><?= $critical === [] ? 'Nobody is paid up without a bed' : 'Paid, with no bed allocated' ?></div>
  </div>
  <div class="tile <?= $warnings !== [] ? 'tile--clay' : '' ?>">
    <div class="tile__label">Worth a look</div>
    <div class="tile__value"><?= count($warnings) ?></div>
    <div class="tile__meta">Missing names and misplaced access needs</div>
  </div>
  <div class="tile tile--gold">
    <div class="tile__label">Beds sold</div>
    <div class="tile__value"><?= (int) $occupancy['booked'] ?> <span style="font-size:var(--step-0);color:var(--ink-muted)">of <?= (int) $occupancy['total_bed_nights'] ?></span></div>
    <?php $pct = (int) $occupancy['total_bed_nights'] > 0 ? (int) round(((int) $occupancy['booked'] / (int) $occupancy['total_bed_nights']) * 100) : 0; ?>
    <div class="bar"><span style="width:<?= max(0, min(100, $pct)) ?>%"></span></div>
    <div class="tile__meta"><?= $pct ?>% of every bed-night, <?= (int) $holds ?> held in carts right now</div>
  </div>
  <?php if ($tightest !== null): ?>
    <div class="tile tile--plum">
      <div class="tile__label">Tightest night</div>
      <div class="tile__value"><?= e($tightest['label']) ?></div>
      <div class="tile__meta"><?= (int) $tightest['available'] ?> bed<?= (int) $tightest['available'] === 1 ? '' : 's' ?> left of <?= (int) $tightest['total'] ?> · <?= (int) $tightest['percent'] ?>% sold</div>
    </div>
  <?php endif; ?>
  <div class="tile <?= $unmatched !== [] ? 'tile--clay' : '' ?>">
    <div class="tile__label">Roommate requests</div>
    <div class="tile__value"><?= count($roommates) ?></div>
    <div class="tile__meta"><?= count($unmatched) ?> not yet in the same room</div>
  </div>
</div>

<?php if ($critical !== []): ?>
  <div class="admin-panel" style="border-left:4px solid var(--error)">
    <h2>Paid for a bed, and does not have one</h2>
    <p class="muted">
      This is the one thing that must never reach the venue unresolved. It happens when a bed hold expires
      during a very slow payment and the bed is gone by the time the notification lands. Open the order, see what
      they paid for, and allocate a bed from the bed board.
    </p>
    <div class="ledger-scroll">
      <table class="ledger">
        <thead><tr><th>Guest</th><th>Order</th><th>Night</th><th>What happened</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($critical as $problem): ?>
            <tr>
              <td><strong><?= e($problem['who']) ?></strong></td>
              <td><a href="<?= e(url('/admin/orders/' . $problem['order_id'])) ?>"><?= e($problem['order_reference']) ?></a></td>
              <td><?= e(za_date($problem['night'], 'D j M')) ?></td>
              <td class="muted"><?= e($problem['reason']) ?></td>
              <td><a class="btn btn--sm" href="<?= e(url('/admin/bookings/board')) ?>">Find a bed</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<div class="admin-grid admin-grid--sidebar">
  <div class="stack-m">

    <!-- ------------------------------------------------------ occupancy -->
    <div class="admin-panel" style="padding:0">
      <div class="admin-panel__head" style="padding:1rem 1.1rem">
        <h2>Where the pressure is</h2>
        <p class="muted">Beds sold and held per room type, night by night. Held means somebody has it in a cart right now.</p>
      </div>
      <div class="ledger-scroll">
        <table class="ledger">
          <thead>
            <tr>
              <th>Room type</th>
              <th class="numeric">Beds</th>
              <?php foreach ($nights as $night): ?>
                <th class="numeric"><?= e(za_date($night, 'D j M')) ?></th>
              <?php endforeach; ?>
              <th class="numeric">Sold</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($byType['rows'] as $row): ?>
              <tr>
                <td>
                  <strong><?= e($row['room_type']['name']) ?></strong>
                  <?php if ((int) $row['room_type']['is_active'] !== 1): ?><br><span class="badge badge--warning">not on sale</span><?php endif; ?>
                </td>
                <td class="numeric"><?= (int) $row['beds'] ?></td>
                <?php foreach ($nights as $night): ?>
                  <?php $n = $row['by_night'][$night]; ?>
                  <td class="numeric">
                    <strong><?= (int) $n['free'] ?></strong> free
                    <div class="muted" style="font-size:.68rem">
                      <?= (int) $n['booked'] ?> sold<?= (int) $n['held'] > 0 ? ', ' . (int) $n['held'] . ' held' : '' ?>
                    </div>
                    <div class="bar <?= $n['percent'] >= 95 ? 'bar--error' : ($n['percent'] >= 80 ? 'bar--warning' : '') ?>">
                      <span style="width:<?= max(0, min(100, (int) $n['percent'])) ?>%"></span>
                    </div>
                  </td>
                <?php endforeach; ?>
                <td class="numeric"><strong><?= (int) $row['percent'] ?>%</strong></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ------------------------------------------------------- roommates -->
    <div class="admin-panel" style="padding:0">
      <div class="admin-panel__head" style="padding:1rem 1.1rem">
        <h2>Roommate requests</h2>
        <p class="muted">
          A request is matched when the person named is actually in the same room on the same night.
          Unmatched ones are at the top, because those are the ones that turn into a conversation at reception.
        </p>
      </div>
      <?php if ($roommates === []): ?>
        <p class="muted" style="padding:0 1.1rem 1.1rem">Nobody has asked to share with anybody yet.</p>
      <?php else: ?>
        <div class="ledger-scroll">
          <table class="ledger">
            <thead><tr><th>Guest</th><th>Asked for</th><th>Night</th><th>Currently with</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php
                usort($roommates, static fn (array $a, array $b): int => ($a['matched'] <=> $b['matched']) ?: strcmp((string) $a['night'], (string) $b['night']));
              ?>
              <?php foreach ($roommates as $request): ?>
                <tr>
                  <td>
                    <strong><?= e((string) $request['guest_name']) ?></strong><br>
                    <span class="muted"><?= e((string) $request['unit_name']) ?> · <?= e((string) $request['bed_label']) ?></span>
                  </td>
                  <td><?= e((string) $request['roommate_request']) ?></td>
                  <td><?= e(za_date((string) $request['night'], 'D j M')) ?></td>
                  <td>
                    <?php if ($request['sharing_with'] === []): ?>
                      <span class="muted">alone in the room</span>
                    <?php else: ?>
                      <?php foreach ($request['sharing_with'] as $other): ?>
                        <?= e((string) ($other['guest_name'] ?: 'unnamed guest')) ?><br>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($request['matched']): ?>
                      <span class="badge badge--success">together</span>
                    <?php elseif (!$request['requested_is_booked']): ?>
                      <span class="badge badge--warning">not booked</span>
                      <div class="muted" style="font-size:.68rem">Nobody by that name has a bed on this night</div>
                    <?php else: ?>
                      <span class="badge badge--warning">apart</span>
                    <?php endif; ?>
                  </td>
                  <td><a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings/' . $request['id'] . '/move')) ?>">Move</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- ------------------------------------------------- special requests -->
    <div class="admin-panel" style="padding:0">
      <div class="admin-panel__head" style="padding:1rem 1.1rem">
        <h2>Special requests</h2>
        <p class="muted">Everything a guest asked for that a person has to do something about. <?= count($requests) ?> in total.</p>
      </div>
      <?php if ($requests === []): ?>
        <p class="muted" style="padding:0 1.1rem 1.1rem">Nothing outstanding.</p>
      <?php else: ?>
        <div class="ledger-scroll">
          <table class="ledger">
            <thead><tr><th>Guest</th><th>Where</th><th>Night</th><th>What they asked for</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($requests as $request): ?>
                <tr>
                  <td>
                    <strong><?= e((string) ($request['guest_name'] ?: 'Unnamed guest')) ?></strong>
                    <?php if ($request['guest_phone']): ?><br><span class="muted"><?= e((string) $request['guest_phone']) ?></span><?php endif; ?>
                  </td>
                  <td><?= e((string) $request['unit_name']) ?><br><span class="muted"><?= e((string) $request['bed_label']) ?></span></td>
                  <td><?= e(za_date((string) $request['night'], 'D j M')) ?></td>
                  <td>
                    <?php if ($request['accessibility_needs']): ?>
                      <span class="badge badge--warning">access</span> <?= e((string) $request['accessibility_needs']) ?><br>
                    <?php endif; ?>
                    <?php if ($request['notes']): ?>
                      <span class="muted"><?= e((string) $request['notes']) ?></span><br>
                    <?php endif; ?>
                    <?php if ($request['customer_note']): ?>
                      <span class="muted">On the order: <?= e(excerpt((string) $request['customer_note'], 90)) ?></span>
                    <?php endif; ?>
                  </td>
                  <td><a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings/' . $request['id'] . '/move')) ?>">Open</a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- ---------------------------------------------------- warning list -->
    <?php if ($warnings !== []): ?>
      <div class="admin-panel" style="padding:0;border-left:4px solid var(--warning)">
        <div class="admin-panel__head" style="padding:1rem 1.1rem">
          <h2>Worth a look</h2>
          <p class="muted">Not urgent, but each one is an awkward conversation at the door if it is left.</p>
        </div>
        <div class="ledger-scroll">
          <table class="ledger">
            <thead><tr><th>What</th><th>Who</th><th>Night</th><th>Why it matters</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($warnings as $problem): ?>
                <tr>
                  <td><span class="badge badge--warning"><?= e($problem['kind']) ?></span></td>
                  <td><?= e($problem['who']) ?></td>
                  <td><?= e(za_date($problem['night'], 'D j M')) ?></td>
                  <td class="muted"><?= e($problem['reason']) ?></td>
                  <td>
                    <?php if (($problem['booking_id'] ?? null) !== null): ?>
                      <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/bookings/' . $problem['booking_id'] . '/move')) ?>">Open</a>
                    <?php elseif (($problem['order_id'] ?? null) !== null): ?>
                      <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/orders/' . $problem['order_id'])) ?>">Order</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- ------------------------------------------------------------ aside -->
  <aside class="stack-m">
    <div class="admin-panel">
      <h2>Arrivals &amp; departures</h2>
      <p class="muted">How many people reception meets each day.</p>
      <?php foreach ($movement['by_day'] as $night => $day): ?>
        <div class="day-row">
          <div class="day-row__date"><?= e(za_date((string) $night, 'D j M')) ?></div>
          <div class="day-row__counts">
            <?php if (count($day['arriving']) > 0): ?>
              <span class="day-row__in">+<?= count($day['arriving']) ?> in</span>
            <?php endif; ?>
            <?php if (count($day['departing']) > 0): ?>
              <span class="day-row__out">&minus;<?= count($day['departing']) ?> out</span>
            <?php endif; ?>
            <span class="muted">
              <?= $day['is_checkout_only'] ? 'check-out morning' : (int) $day['staying'] . ' in beds' ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
      <p class="field__hint" style="margin-top:.75rem">
        A guest arrives on their first booked night and leaves the morning after their last, so the check-out
        morning is shown even though no bed is sold for it.
      </p>
    </div>

    <div class="admin-panel">
      <h2>Every stay</h2>
      <p class="muted"><?= count($movement['stays']) ?> guests on the estate across the weekend.</p>
      <div class="ledger-scroll">
        <table class="ledger">
          <tbody>
            <?php foreach (array_slice($movement['stays'], 0, 40) as $stay): ?>
              <tr>
                <td>
                  <strong><?= e((string) ($stay['guest_name'] ?: 'Unnamed')) ?></strong><br>
                  <span class="muted"><?= e((string) $stay['units']) ?></span>
                </td>
                <td class="numeric">
                  <?= e(za_date((string) $stay['first_night'], 'j M')) ?>–<?= e(za_date(date('Y-m-d', strtotime((string) $stay['last_night'] . ' +1 day')), 'j M')) ?><br>
                  <span class="muted"><?= (int) $stay['nights'] ?> night<?= (int) $stay['nights'] === 1 ? '' : 's' ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if (count($movement['stays']) > 40): ?>
        <p class="muted" style="margin-top:.6rem">
          Showing the first 40. The rooming list CSV has every one of them.
        </p>
      <?php endif; ?>
    </div>

    <div class="admin-panel">
      <h3>How a bed actually moves</h3>
      <ol class="checklist">
        <li>Moving a guest never cancels anything. The booking keeps its reference and its price; only the bed changes.</li>
        <li>The list of destination beds only shows beds that are genuinely free on that night — nothing booked, nothing in somebody's cart.</li>
        <li>If the bed is taken between opening the page and pressing the button, the database refuses the move and the guest keeps the bed they had. You will see a message saying so.</li>
        <li>Every move is written to the audit log with who did it and when.</li>
      </ol>
    </div>
  </aside>
</div>
<?php View::stop(); ?>
