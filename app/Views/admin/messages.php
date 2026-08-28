<?php
use App\Core\View;
View::layout('layouts.admin');
View::start('content');
?>
<div class="admin-head">
  <div><h1>Contact messages</h1><p><?= (int) $unread ?> unread</p></div>
  <a class="btn btn--sm btn--ghost" href="<?= e(url('/admin/export/messages')) ?>">Export CSV</a>
</div>

<div class="admin-toolbar">
  <form method="get" action="<?= e(url('/admin/messages')) ?>" data-autosubmit>
    <select name="status">
      <option value="">Any status</option>
      <?php foreach (['new', 'read', 'replied', 'archived'] as $value): ?>
        <option value="<?= $value ?>" <?= $status === $value ? 'selected' : '' ?>><?= e(ucfirst($value)) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn--sm" type="submit">Filter</button>
  </form>
</div>

<?php foreach ($result['rows'] as $message): ?>
  <div class="admin-panel">
    <div class="cluster cluster--between" style="align-items:flex-start">
      <div>
        <h2 style="margin-bottom:.2rem"><?= e($message['subject']) ?>
          <span class="badge <?= $message['status'] === 'new' ? 'badge--gold' : '' ?>"><?= e($message['status']) ?></span>
        </h2>
        <p class="muted" style="font-size:var(--step--1);margin:0">
          <strong><?= e($message['name']) ?></strong> &middot;
          <a href="mailto:<?= e($message['email']) ?>"><?= e($message['email']) ?></a>
          <?php if ($message['phone']): ?> &middot; <?= e($message['phone']) ?><?php endif; ?>
          &middot; <?= e(za_date((string) $message['created_at'], 'j M Y, H:i')) ?>
        </p>
      </div>
      <a class="btn btn--sm btn--ghost" href="mailto:<?= e($message['email']) ?>?subject=<?= rawurlencode('Re: ' . $message['subject']) ?>">Reply by email</a>
    </div>

    <blockquote style="border-left:3px solid var(--gold);padding-left:1rem;margin:1rem 0;color:var(--ink-soft)">
      <?= nl2br(e($message['message'])) ?>
    </blockquote>

    <form method="post" action="<?= e(url('/admin/messages/' . $message['id'])) ?>" class="inline-form">
      <?= csrf_field() ?>
      <select name="status">
        <?php foreach (['new', 'read', 'replied', 'archived'] as $value): ?>
          <option value="<?= $value ?>" <?= $message['status'] === $value ? 'selected' : '' ?>><?= e(ucfirst($value)) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="admin_notes" value="<?= e((string) $message['admin_notes']) ?>" placeholder="Internal note" style="min-width:260px">
      <button class="btn btn--sm" type="submit">Save</button>
    </form>
  </div>
<?php endforeach; ?>

<?php if ($result['rows'] === []): ?>
  <div class="empty-state"><div class="empty-state__icon">✉️</div><h3>No messages</h3><p>Enquiries from the contact form arrive here.</p></div>
<?php endif; ?>

<?php View::include('partials.pagination', ['result' => $result, 'query' => ['status' => $status]]); ?>
<?php View::stop(); ?>
