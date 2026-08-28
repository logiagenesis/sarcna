<?php
use App\Core\View;
View::layout('layouts.public');
View::start('content');
?>
<section class="section">
  <div class="container container--narrow">
    <ol class="steps">
      <li class="is-done">Cart</li><li class="is-done">Details</li><li class="is-current">Payment</li><li>Confirmed</li>
    </ol>

    <div class="form-panel text-center">
      <?php if ($sandbox): ?>
        <div class="alert alert--warning"><div><div class="alert__title">Sandbox mode</div>
        <p>This payment runs against the PayFast sandbox. No real money moves.</p></div></div>
      <?php endif; ?>

      <h1 style="font-size:var(--step-3)">Taking you to PayFast</h1>
      <p class="muted" style="margin-inline:auto">Order <strong><?= e($order['reference']) ?></strong> &middot; <?= e(money((int) $order['total_cents'])) ?></p>

      <div class="table-wrap" style="margin:1.5rem 0;text-align:left">
        <table>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= e($item['description']) ?><?= (int) $item['quantity'] > 1 ? ' × ' . (int) $item['quantity'] : '' ?></td>
                <td class="numeric"><?= e(money((int) $item['total_cents'])) ?></td>
              </tr>
            <?php endforeach; ?>
            <tr><th>Total</th><th class="numeric"><?= e(money((int) $order['total_cents'])) ?></th></tr>
          </tbody>
        </table>
      </div>

      <form id="payfast-form" method="post" action="<?= e($processUrl) ?>">
        <?php foreach ($fields as $name => $value): ?>
          <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
        <?php endforeach; ?>
        <button class="btn btn--gold btn--lg btn--block" type="submit">Continue to PayFast</button>
      </form>

      <p class="muted" style="font-size:var(--step--1);margin-top:1rem">
        If you are not redirected within a few seconds, press the button above.
        Your booking is confirmed once PayFast tells us the payment succeeded.
      </p>
      <a class="link-arrow" href="<?= e(url('/cart')) ?>" style="margin-top:1rem;display:inline-flex">&larr; Back to cart</a>
    </div>
  </div>
</section>
<?php View::stop(); ?>

<?php View::start('scripts'); ?>
<script>
  // Give the customer a moment to read the summary, then hand over to PayFast.
  window.setTimeout(function () {
    if (window.sarcnaTrack) { window.sarcnaTrack('payfast_redirect', { value: <?= (int) $order['total_cents'] / 100 ?>, currency: 'ZAR' }); }
    document.getElementById('payfast-form').submit();
  }, 2500);
</script>
<?php View::stop(); ?>
