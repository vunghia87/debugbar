<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <title>Watcher</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="<?= debugbar()->getConfig()['assets_sites_url']?>/vendor/watcher/app-dark.css" rel="stylesheet" type="text/css">
</head>
<body>
<div id="telescope">
    <div class="container-fluid mb-5">
        <?php include 'head.php' ?>

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