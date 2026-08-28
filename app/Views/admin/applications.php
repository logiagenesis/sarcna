<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Service applications</h1><p><?= (int) $result['total'] ?> matching</p></div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/applications')) ?>">Export CSV</a>
</div>

<div class="tiles">
  <?php foreach ($counts as $row): ?>
    <a class="tile <?= $row['status'] === 'new' ? 'tile--gold' : '' ?>" href="<?= e(url('/admin/applications?status=' . $row['status'])) ?>">
      <div class="tile__label"><?= e(ucfirst((string) $row['status'])) ?></div>
      <div class="tile__value"><?= (int) $row['total'] ?></div>
    </a>
  <?php endforeach; ?>
</div>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/applications')) ?>" data-autosubmit>
    <select name="status">
      <option value="">Any status</option>
      <?php foreach (['new', 'reviewing', 'accepted', 'waitlisted', 'declined'] as $value): ?>
        <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= e(ucfirst($value)) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="area">
      <option value="">Any service area</option>
      <?php foreach ($areas as $value): ?>
        <option value="<?= e($value) ?>" <?= $area === $value ? 'selected' : '' ?>><?= e($value) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Filter</button>
    <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/applications')) ?>">Clear</a>
  </form>
</div>

<div class="admin-panel" style="padding:0">
  <div class="table-wrap" style="border:0">
    <table class="admin-table">
      <thead><tr><th>Applicant</th><th>Contact</th><th>Areas</th><th>Availability</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($result['rows'] as $application): ?>
          <tr>
            <td><a href="<?= e(url('/admin/applications/' . $application['id'])) ?>"><strong><?= e($application['name']) ?></strong></a><br>
                <span class="muted"><?= e($application['reference']) ?> &middot; <?= e(za_date((string) $application['created_at'], 'j M')) ?></span></td>
            <td><?= e($application['email']) ?><br><span class="muted"><?= e($application['phone']) ?></span></td>
            <td style="max-width:280px"><?= e((string) $application['service_areas']) ?></td>
            <td><?= e((string) $application['availability']) ?></td>
            <td><span class="badge <?= $application['status'] === 'accepted' ? 'badge--success' : ($application['status'] === 'new' ? 'badge--gold' : '') ?>"><?= e($application['status']) ?></span></td>
            <td><a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/applications/' . $application['id'])) ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
        <?php if ($result['rows'] === []): ?><tr><td colspan="6" class="muted">No applications match.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php View::include('partials.pagination', ['result' => $result, 'query' => ['status' => $status, 'area' => $area]]); ?>
<?php View::stop(); ?>
