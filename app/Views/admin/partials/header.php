<!DOCTYPE html>
<html lang="pt-br" dir="ltr" data-bs-theme="dark" data-color-theme="Cyan_Theme" data-layout="vertical">

<head>
    <!-- Required meta tags -->
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <!-- Favicon icon-->
    <link rel="shortcut icon" type="image/png" href="<?php echo base_url() ?>assets/images/logos/favicon.png" />

    <!-- Core Css -->
    <link rel="stylesheet" href="<?php echo base_url() ?>assets/css/styles.css" />
    <?php if (isset($css) && is_array($css)): ?>
        <?php foreach ($css as $cssFile): ?>
            <link href="<?= base_url($cssFile) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>

    <title>AppMax - <?php echo $titulo; ?></title>

</head>

<body>
    <!-- Preloader -->
    <div class="preloader">
        <img src="<?php echo base_url() ?>assets/images/logos/favicon.png" alt="loader" class="lds-ripple img-fluid" />
    </div>

    <div id="main-wrapper">