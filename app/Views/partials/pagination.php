<?php
/** @var array $result @var array|null $query */
if (($result['pages'] ?? 1) <= 1) {
    return;
}

$query = $query ?? [];
$link  = static function (int $page) use ($query): string {
    return '?' . http_build_query(array_merge(array_filter($query, static fn ($v): bool => $v !== '' && $v !== 0), ['page' => $page]));
};

$current = (int) $result['page'];
$pages   = (int) $result['pages'];
$from    = max(1, $current - 2);
$to      = min($pages, $current + 2);
?>
<nav class="pagination" aria-label="Pagination">
  <?php if ($current > 1): ?><a href="<?= e($link($current - 1)) ?>">&larr; Previous</a><?php endif; ?>
  <?php if ($from > 1): ?><a href="<?= e($link(1)) ?>">1</a><?php if ($from > 2): ?><span>…</span><?php endif; endif; ?>
  <?php for ($page = $from; $page <= $to; $page++): ?>
    <?php if ($page === $current): ?><span class="is-current"><?= $page ?></span>
    <?php else: ?><a href="<?= e($link($page)) ?>"><?= $page ?></a><?php endif; ?>
  <?php endfor; ?>
  <?php if ($to < $pages): ?><?php if ($to < $pages - 1): ?><span>…</span><?php endif; ?><a href="<?= e($link($pages)) ?>"><?= $pages ?></a><?php endif; ?>
  <?php if ($current < $pages): ?><a href="<?= e($link($current + 1)) ?>">Next &rarr;</a><?php endif; ?>
  <span style="border:0;background:none;color:var(--ink-muted)"><?= (int) $result['total'] ?> total</span>
</nav>
