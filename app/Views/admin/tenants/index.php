<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Tenants</title>
  <style>
    body { font-family: system-ui, -apple-system, Segoe UI, Roboto, Ubuntu, Cantarell, 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif; margin: 2rem; }
    .box { max-width: 720px; margin: 0 auto; padding: 1.5rem; border: 1px solid #ddd; border-radius: 8px; }
    h1 { margin-top: 0; }
    ul { padding-left: 1.25rem; }
    li { margin: .25rem 0; }
  </style>
}</head>
<body>
  <div class="box">
    <h1>Tenants</h1>
    <?php if (!empty($tenants)) : ?>
      <ul>
        <?php foreach ($tenants as $t) : ?>
          <li>#<?= esc($t['id']) ?> — <?= esc($t['name']) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php else : ?>
      <p>Nenhum tenant encontrado.</p>
    <?php endif; ?>
  </div>
</body>
</html>