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

                <div class="card">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link">Meta</a></li>
                        <li class="nav-item"><a href="#request" class="nav-link">Request</a></li>
                        <li class="nav-item"><a href="#database" class="nav-link">Database</a></li>
                    </ul>
                    <div class="table-responsive">
                        <table class="table mb-0 card-bg-secondary table-borderless">
                            <tbody>
                            <tr>
                                <td class="table-fit font-weight-bold">Time</td>
                                <td><?= $data['__meta']['datetime'] ?> (<?= time_human($data['__meta']['datetime']) ?>)</td>
                            </tr>
                            <tr>
                                <td class="table-fit font-weight-bold">Uri</td>
                                <td><?= $data['__meta']['uri'] ?></td>
                            </tr>
                            <tr>
                                <td class="table-fit font-weight-bold">Action</td>
                                <td><?= $data['__meta']['action'] ?></td>
                            </tr>
                            <tr>
                                <td class="table-fit font-weight-bold">Ip</td>
                                <td><?= $data['__meta']['ip'] ?></td>
                            </tr>
                            <tr>
                                <td class="table-fit font-weight-bold">Status</td>
                                <td><?= $data['request']['status_code'] ?? 200 ?></td>
                            </tr>
                            <tr>
                                <td class="table-fit font-weight-bold">Memory</td>
                                <td><?= $data['memory']['peak_usage_str'] ?? 0 ?></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="card mt-3" id="request">
                    <div class="card-header d-flex align-items-center justify-content-between">Request</div>
                    <div class="">
                        <?php foreach ($data['request']['data'] ?? [] as $index => $item) :?>
                        <div class="card-bg-secondary px-4 py-2" style="border-top: 1px solid #120f12">
                            <p class="mb-2 text-white font-weight-bold"><?= $item['method'] ?? '' ?></p>
                            <?= $item['value'] ?? '' ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card mt-3" id="database">
                    <div class="card-header d-flex align-items-center justify-content-between">Queries</div>
                    <table class="table table-striped table-fixed table-sm mb-0" style="table-layout: fixed">
                        <thead>
                        <tr>
                            <th style="width: 60%">Query <br><small><?= count($data['pdo']['statements'] ?? []) ?> queries, <span id="duplicate">0</span> of which are duplicated.</small>
                            </th>
                            <th>Duration<br><small><?= $data['pdo']['accumulated_duration_str'] ?? '' ?></small></th>
                            <th>Memory<br><small><?= $data['pdo']['memory_usage_str'] ?? '' ?></small></th>
                            <th>Row <br><small>&nbsp;</small></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($data['pdo']['statements'] ?? [] as $index => $item) :?>
                        <tr>
                            <td>
                                <div class="text-ellipsis" style="width: 100%;">
                                    <span class="dot <?= $item['is_success'] ? 'success' : 'error' ?>"></span> <?= $item['error_message'] ?? '' ?>
                                    <?= $item['sql'] ?? '' ?>
                                </div>
                            </td>
                            <td><?= $item['duration_str'] ?? ''?></td>
                            <td><?= $item['memory_str'] ?? ''?></td>
                            <td><?= $item['row_count'] ?? 0 ?></td>
                            <td>Copy</td>
                        </tr>
<!--                        <tr>-->
<!--                            <td colspan="5">-->
<!--                                <pre class="pre" lang="sql"><code>--><?//= $item['sql'] ?? ''?><!--</code></pre>-->
<!--                                <hr>-->
<!--                                --><?//= new \DebugBar\Dumper\Framer($item['backtrace']) ?>
<!--                            </td>-->
<!--                        </tr>-->
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>