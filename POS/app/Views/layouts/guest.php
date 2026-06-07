<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'POSG') ?></title>
    <link rel="stylesheet" href="<?= url('/assets/vendor/bootstrap/bootstrap.rtl.min.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/app.css') ?>">
</head>
<body class="guest-bg">
    <?= $content ?>
</body>
</html>
