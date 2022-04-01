<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="robots" content="noindex, nofollow">
    <title>Watcher</title>
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    <link href="<?= debugbar()->getConfig()['assets_sites_url']?>/vendor/watcher/app-dark.css" rel="stylesheet" type="text/css">
    <script src="<?= debugbar()->getConfig()['assets_sites_url']?>/vendor/watcher/app.js"></script>

    <link href="<?= $assetUrl ?>/vendor/dumper/dumper.css" rel="stylesheet" type="text/css">
    <script src="<?= $assetUrl ?>/vendor/dumper/dumper.js"></script>
</head>
<body>
<div id="telescope">
    <div class="d-flex align-items-center py-2 px-3 header">
        <?php include 'logo.php' ?>
        <?php include 'menu.php' ?>
        <?php include 'control.php' ?>
    </div>
    <div class="container-fluid content mb-5">
        <div class="row">
            <?php include 'nav.php' ?>
            <div class="col-10" id="request">
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between p-0">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a href="#" class="nav-link active">Request</a></li>
                        </ul>
                    </div>

                    <div class="table-responsive mt-2">
                        <table class="table mb-0 card-bg-secondary table-borderless">
                            <tbody>
                            <tr>
                                <td class="table-fit font-weight-bold">Time</td>
                                <td><?= $data['__meta']['datetime'] ?? '' ?> (<?= time_human($data['__meta']['datetime'] ?? 0) ?>)</td>
                            </tr>
                            <tr>
                                <td class="table-fit font-weight-bold">Uri</td>
                                <td><?= $data['__meta']['uri'] ?? '' ?></td>
                            </tr>
                            <tr>
                                <td class="table-fit font-weight-bold">Action</td>
                                <td><?= $data['__meta']['action'] ?? '' ?></td>
                            </tr>
                            <tr>
                                <td class="table-fit font-weight-bold">Ip</td>
                                <td><?= $data['__meta']['ip'] ?? '' ?></td>
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

                    <div class="mt-2">
                        <?php foreach ($data['request']['data'] ?? [] as $index => $item) :?>
                            <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                <p class="m-0 text-white font-weight-bold"><?= $item['method'] ?? '' ?></p>
                                <?= $item['value'] ?? '' ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card mt-3" id="response">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link active">Response</a></li>
                    </ul>
                    <div>
                        <?php foreach ($data['response'] ?? [] as $key => $value) :?>
                            <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                <p class="mb-2 mt-0 text-white font-weight-bold"><?= $key ?? '' ?></p>
                                <?= $value ?? '' ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card mt-3" id="auth">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link active">Auth</a></li>
                    </ul>
                    <div>
                        <?php foreach ($data['auth'] ?? [] as $key => $value) :?>
                            <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                <?= $value ?? '' ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card mt-3" id="exception">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link active">Exception (<?= $data['exceptions']['count'] ?? 0 ?>)</a></li>
                    </ul>
                    <div>
                        <?php foreach ($data['exceptions']['exceptions'] ?? [] as $key => $value) :?>
                            <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                <p class="m-0 font-weight-bold">
                                    \<?= $value['type'] ?? '' ?> (<?= $value['code'] ?? '' ?>)
                                    <small class="text-muted">
                                        <?= $value['file'] ?>:<?= $value['line'] ?> <a target="_blank" href="<?= $value['editor_href'] ?>" class="">#</a>
                                    </small>
                                </p>
                                <p class="mb-2 mt-0 text-danger"><?= $value['message'] ?? '' ?></p>
                                <pre class="pre-code">
                                    <?php $star = $value['start'] ?? 0; $line = $value['line'] ?? 0 ?>
                                    <ol start="<?= $value['start'] ?>">
                                       <?php foreach ($value['surrounding_lines'] ?? [] as $index => $code) :?>
                                           <li class="<?= $index + $star == $line - 1 ?? 0 ? 'current' : '' ?>"><?= $code ?></li>
                                       <?php endforeach; ?>
                                    </ol>
                                </pre>
                                <ol class="m-0 pl-3 mt-2">
                                    <?php foreach (explode(PHP_EOL, $value['stack_trace'] ?? '') as $index => $code) :?>
                                        <li class="text-muted small"><?= $code ?> <a target="_blank" href="<?= $value['stack_trace_links'][$index] ?? '#' ?>" class="">#</a></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card mt-3" id="database">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link active">Queries (<?= count($data['pdo']['statements'] ?? []) ?>)</a></li>
                    </ul>
                    <table class="table table-striped table-fixed table-sm mb-0">
                        <thead>
                        <tr>
                            <th style="width: 60%">Query <br><small><span id="duplicate">0</span> of which are duplicated.</small></th>
                            <th>Row <br><small>&nbsp;</small></th>
                            <th>Duration<br><small><?= $data['pdo']['accumulated_duration_str'] ?? '' ?></small></th>
                            <th>Memory<br><small><?= $data['pdo']['memory_usage_str'] ?? '' ?></small></th>
                            <th class="text-right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($data['pdo']['statements'] ?? [] as $index => $item) :?>
                        <tr>
                            <td>
                                <div class="text-ellipsis" style="width: 100%;" title="<?= $item['sql'] ?? '' ?>">
                                    <span class="dot <?= $item['is_success'] ? 'success' : 'error' ?>"></span> <?= $item['error_message'] ?? '' ?>
                                    <?= $item['sql'] ?? '' ?>
                                </div>
                            </td>
                            <td><?= $item['row_count'] ?? 0 ?></td>
                            <td><?= $item['duration_str'] ?? ''?></td>
                            <td><?= $item['memory_str'] ?? ''?></td>
                            <td class="text-right">Copy</td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card mt-3" id="memcache">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link active">Memcache (<?= $data['memcache']['count'] ?? 0 ?>)</a></li>
                    </ul>
                    <table class="table table-striped table-fixed table-sm mb-0">
                        <thead>
                        <tr>
                            <th style="width: 60%">Key</th>
                            <th>Label</th>
                            <th>Timelife</th>
                            <th class="text-right">Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($data['memcache']['memcaches'] ?? [] as $index => $item) :?>
                        <tr>
                            <td>
                                <div class="text-ellipsis" style="width: 100%;" title="<?= $item['key'] ?? '' ?>">
                                    <?= $item['key'] ?? '' ?>
                                </div>
                                <?= isset($item['value']) ? '<br>'. $item['value'] : '' ?>
                            </td>
                            <td><?= $item['label'] ?? ''?></td>
                            <td><?= isset($item['timeLife']) ?  $item['timeLife'] .' second' : '' ?></td>
                            <td class="text-right">Copy</td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card mt-3" id="message">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link active">Message (<?= $data['messages']['count'] ?? 0 ?>)</a></li>
                    </ul>
                    <div>
                        <?php foreach ($data['messages']['messages'] ?? [] as $key => $value) :?>
                            <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                <p class="mb-2 text-white font-weight-bold"><?= $value['label'] ?? '' ?> | <a href="#">Backtrace</a></p>
                                <?= $value['message_html'] ?? '' ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="card mt-3" id="command">
                    <ul class="nav nav-pills">
                        <li class="nav-item"><a href="#" class="nav-link active">Command (<?= $data['command']['count'] ?? 0 ?>)</a></li>
                    </ul>
                    <div>
                        <?php foreach ($data['command']['commands'] ?? [] as $key => $value) :?>
                            <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                <p class="mb-2 text-white font-weight-bold"><?= $value['command'] ?? '' ?> | <a href="#">Backtrace</a></p>
                                <p><strong class="text-muted">Agruments:</strong> <small><?= $value['arguments'] ?? '' ?> </small></p>
                                <p><strong class="text-muted">Options:</strong> <small><?= $value['options'] ?? '' ?></small> </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
</div>
</body>
</html>