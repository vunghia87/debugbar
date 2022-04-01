<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <title>Watcher</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <?php $assetUrl = debugbar()->getAssetPath() ?>
    <link href="<?= $assetUrl ?>/vendor/watcher/app-dark.css" rel="stylesheet" type="text/css">
    <link href="<?= $assetUrl ?>/vendor/dumper/dumper.css" rel="stylesheet" type="text/css">
    <script src="<?= $assetUrl ?>/vendor/dumper/dumper.js"></script>
</head>
<body>
<div id="telescope">

    <div class="d-flex align-items-center py-2 px-3 header">
        <?php include 'logo.php' ?>
        <?php include 'control.php' ?>
    </div>

    <div class="container-fluid mb-5 content">
        <div class="row mt-4">
            <?php include 'nav.php' ?>

            <div class="col-10">
                <?php
                switch ($request['type']) {
                    case 'request':
                    default:
                        include 'request.php';
                }
                ?>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>