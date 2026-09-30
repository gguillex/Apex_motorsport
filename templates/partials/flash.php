<?php $messages = pull_flashes(); ?>
<?php if ($messages): ?>
  <div class="flash-stack" role="status">
    <?php foreach ($messages as $msg): ?>
      <p class="alert alert--<?= e($msg['type']) ?>"><?= e($msg['message']) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
