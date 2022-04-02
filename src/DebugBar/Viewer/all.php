<!DOCTYPE html>
<html lang="en">
<head>
    <title>Watcher</title>
    <?php include 'style.php' ?>
</head>
<body>
<div id="watcher">
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