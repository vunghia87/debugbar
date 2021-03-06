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
        <?php include 'menu.php' ?>
        <?php include 'control.php' ?>
        <button onclick="collapseAll()" class="btn btn-outline-primary ml-3" title="Collapse">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon fill-primary" fill="none" viewBox="0 0 22 22"
                 stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
            </svg>
        </button>
    </div>
    <div class="container-fluid content mb-5" id="content" data-id="<?= $request['id'] ?>">
        <div class="row">
            <div class="col-12">
                <div class="card" id="request">
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
                                <td>
                                    <?= $data['__meta']['datetime'] ?? '' ?>
                                    (<?= time_human($data['__meta']['datetime'] ?? 0) ?>)
                                </td>
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
                            <?php if (!empty($data['xdebug_trace']['trace_file'])): ?>
                                <tr id="trace">
                                    <td class="table-fit font-weight-bold">XTrace</td>
                                    <?php $fileXTrace = $data['xdebug_trace']['trace_file'] ?? '' ?>
                                    <td>
                                        <a target="_blank" href="?openPhpDebugBar=true&op=xtrace&file=<?= $fileXTrace ?>"><?= $fileXTrace ?></a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-2">
                        <?php foreach ($data['request']['data'] ?? [] as $index => $item) : ?>
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
                        <?php foreach ($data['response'] ?? [] as $key => $value) : ?>
                            <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                <p class="mb-2 mt-0 text-white font-weight-bold"><?= $key ?? '' ?></p>
                                <?= $value ?? '' ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php if (!empty($data['auth'])): ?>
                    <div class="card mt-3" id="auth">
                        <ul class="nav nav-pills">
                            <li class="nav-item"><a href="#" class="nav-link active">Auth</a></li>
                        </ul>
                        <div>
                            <?php foreach ($data['auth'] ?? [] as $key => $value) : ?>
                                <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                    <?= $value ?? '' ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($data['exceptions']['count'])): ?>
                    <div class="card mt-3" id="exception">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    Exception (<?= $data['exceptions']['count'] ?? 0 ?>)
                                </a>
                            </li>
                        </ul>
                        <div>
                            <?php foreach ($data['exceptions']['exceptions'] ?? [] as $key => $value) : ?>
                                <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                    <p class="m-0 font-weight-bold">
                                        \<?= $value['type'] ?? '' ?> (<?= $value['code'] ?? '' ?>)
                                        <small class="text-muted">
                                            <?= $value['file'] ?>:<?= $value['line'] ?>
                                            <a target="_blank"
                                               href="<?= $value['editor_href'] ?>"
                                               class="">#</a>
                                        </small>
                                    </p>
                                    <p class="mb-2 mt-0 text-danger"><?= $value['message'] ?? '' ?></p>
                                    <pre class="pre-code">
                                        <?php $star = $value['start'] ?? 0;
                                        $line = $value['line'] ?? 0 ?>
                                        <ol start="<?= $value['start'] ?>">
                                           <?php foreach ($value['surrounding_lines'] ?? [] as $index => $code) : ?>
                                               <li class="<?= $index + $star == $line - 1 ?? 0 ? 'current' : '' ?>"><?= $code ?></li>
                                           <?php endforeach; ?>
                                        </ol>
                                    </pre>
                                    <ol class="m-0 pl-3 mt-2">
                                        <?php foreach (explode(PHP_EOL, $value['stack_trace'] ?? '') as $index => $code) : ?>
                                            <li class="text-muted small"><?= $code ?>
                                                <a target="_blank"
                                                   href="<?= $value['stack_trace_links'][$index] ?? '#' ?>"
                                                   class="">#</a></li>
                                        <?php endforeach; ?>
                                    </ol>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($data['pdo']['statements'])): ?>
                    <div class="card mt-3" id="pdo">
                        <div class="card-header p-0 d-flex align-items-center justify-content-between">
                            <ul class="nav nav-pills">
                                <li class="nav-item">
                                    <a href="#" class="nav-link active">
                                        Queries (<?= count($data['pdo']['statements'] ?? []) ?>)
                                    </a>
                                </li>
                            </ul>
                            <input type="text" id="searchQuery" placeholder="insert table ..."
                                   class="form-control w-25">
                        </div>
                        <table class="table table-fixed table-sm mb-0">
                            <thead>
                            <tr>
                                <th style="width: 60%">
                                    Query<br><small><span id="unique">0</span> of which are unique.</small>
                                </th>
                                <th>
                                    Row<br><small>&nbsp;</small>
                                </th>
                                <th>
                                    Duration<br><small><?= $data['pdo']['accumulated_duration_str'] ?? '' ?></small>
                                </th>
                                <th>
                                    Memory<br><small><?= $data['pdo']['memory_usage_str'] ?? '' ?></small>
                                </th>
                                <th style="width: 12%" class="text-right">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($data['pdo']['statements'] ?? [] as $index => $item) : ?>
                                <tr>
                                    <td>
                                        <div class="text-ellipsis" style="width: 100%;"
                                             title="<?= $item['sql'] ?? '' ?>">
                                            <span class="dot <?= $item['is_success'] ? 'success' : 'error' ?>"></span>
                                            <?= $item['error_message'] ?? '' ?>
                                            <span class="sql">
                                                <?= $item['sql'] ?? '' ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td><?= $item['row_count'] ?? 0 ?></td>
                                    <td><?= $item['duration_str'] ?? '' ?></td>
                                    <td><?= $item['memory_str'] ?? '' ?></td>
                                    <td class="text-right">
                                        <div class="btn-group">
                                            <button class="btn-query-trace btn btn-sm btn-outline-info text-white">
                                                Backtrace
                                            </button>
                                            <button class="btn-code btn btn-sm btn-outline-danger text-white"
                                                    data-index="<?= $index ?>">Code
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="hidden">
                                    <td colspan="5">
                                        <ol class="bt">
                                            <?php foreach ($item['backtrace'] ?? [] as $trace) : ?>
                                                <li>
                                                    <div class="bg-secondary text-light small"> <?= $trace['file'] ?? '' ?>
                                                        :<?= $trace['line'] ?? '' ?>
                                                        <a target="_blank"
                                                           href="<?= $trace['editorHref'] ?? '' ?>">#</a></div>
                                                    <div class="text-muted small">
                                                        <?= is_object($trace['class'] ?? '') ? get_class($trace['class']) : $trace['class'] ?><?= $trace['type'] ?? '' ?><?= $trace['function'] ?? '' ?><?php if (is_array($trace['object'])): ?>
                                                            (<?= implode(', ', array_map(function ($v, $k) {
                                                                return sprintf("%s='%s'", $k, $v);
                                                            }, $trace['object'], array_keys($trace['object']))); ?>)
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ol>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (!empty($data['messages']['count'])): ?>
                    <div class="card mt-3" id="message">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    Message (<?= $data['messages']['count'] ?? 0 ?>)
                                </a>
                            </li>
                        </ul>
                        <div class="card-content">
                            <?php foreach ($data['messages']['messages'] ?? [] as $index => $item) : ?>
                                <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                    <div class="d-flex mb-2">
                                        <div class="text-white p-1 font-weight-bold bg-<?= $item['label'] ?? '' ?>">
                                            <?= $item['label'] ?? '' ?>
                                        </div>
                                        <div class="ml-auto">
                                            <div class="btn-group">
                                                <button class="btn-mes-trace btn btn-sm btn-outline-info text-white">
                                                    Backtrace
                                                </button>
                                                <button class="btn-code btn btn-sm btn-outline-danger text-white"
                                                        data-index="<?= $index ?>">Code
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <?= $item['message_html'] ?? '' ?>
                                </div>
                                <ol class="bt hidden">
                                    <?php foreach ($item['backtrace'] ?? [] as $trace) : ?>
                                        <li>
                                            <div class="bg-secondary text-light small">
                                                <?= $trace['file'] ?? '' ?>:<?= $trace['line'] ?? '' ?>
                                                <a target="_blank" href="<?= $trace['editorHref'] ?? '' ?>">#</a>
                                            </div>
                                            <div class="text-muted small">
                                                <?= is_object($trace['class'] ?? '') ? get_class($trace['class']) : $trace['class'] ?><?= $trace['type'] ?? '' ?><?= $trace['function'] ?? '' ?><?php if (is_array($trace['object'])): ?>
                                                    (<?= implode(', ', array_map(function ($v, $k) {
                                                        return sprintf("%s='%s'", $k, $v);
                                                    }, $trace['object'], array_keys($trace['object']))); ?>)
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($data['memcache']['count'])): ?>
                    <div class="card mt-3" id="memcache">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    Memcache (<?= $data['memcache']['count'] ?? 0 ?>)
                                </a>
                            </li>
                        </ul>
                        <table class="table table-fixed table-sm mb-0">
                            <thead>
                            <tr>
                                <th style="width: 60%">Key</th>
                                <th>Label</th>
                                <th>Timelife</th>
                                <th class="text-right">Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($data['memcache']['memcaches'] ?? [] as $index => $item) : ?>
                                <tr>
                                    <td>
                                        <div class="text-ellipsis" style="width: 100%;"
                                             title="<?= $item['key'] ?? '' ?>">
                                            <?= $item['key'] ?? '' ?>
                                        </div>
                                        <?= isset($item['value']) ? '<br>' . $item['value'] : '' ?>
                                    </td>
                                    <td><?= $item['label'] ?? '' ?></td>
                                    <td><?= isset($item['timeLife']) ? $item['timeLife'] . ' second' : '' ?></td>
                                    <td class="text-right">
                                        <div class="btn-group">
                                            <button class="btn-query-trace btn btn-sm btn-outline-info text-white">
                                                Backtrace
                                            </button>
                                            <button class="btn-code btn btn-sm btn-outline-danger text-white"
                                                    data-index="<?= $index ?>">Code
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="hidden">
                                    <td colspan="4">
                                        <ol class="bt">
                                            <?php foreach ($item['backtrace'] ?? [] as $trace) : ?>
                                                <li>
                                                    <div class="bg-secondary text-light small">
                                                        <?= $trace['file'] ?? '' ?>:<?= $trace['line'] ?? '' ?>
                                                        <a target="_blank"
                                                           href="<?= $trace['editorHref'] ?? '' ?>">#</a>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <?= is_object($trace['class'] ?? '') ? get_class($trace['class']) : $trace['class'] ?><?= $trace['type'] ?? '' ?><?= $trace['function'] ?? '' ?><?php if (is_array($trace['object'])): ?>
                                                            (<?= implode(', ', array_map(function ($v, $k) {
                                                                return sprintf("%s='%s'", $k, $v);
                                                            }, $trace['object'], array_keys($trace['object']))); ?>)
                                                        <?php endif; ?>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ol>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if (!empty($data['command']['count'])): ?>
                    <div class="card mt-3" id="command">
                        <ul class="nav nav-pills">
                            <li class="nav-item">
                                <a href="#" class="nav-link active">
                                    Command (<?= $data['command']['count'] ?? 0 ?>)
                                </a>
                            </li>
                        </ul>
                        <div class="card-content">
                            <?php foreach ($data['command']['commands'] ?? [] as $index => $item) : ?>
                                <div class="card-bg-secondary px-3 py-2" style="border-top: 1px solid #120f12">
                                    <div class="d-flex mb-2">
                                        <div class="text-white font-weight-bold">
                                            <?= $item['command'] ?? '' ?>
                                        </div>
                                        <div class="ml-auto">
                                            <div class="btn-group">
                                                <button class="btn-mes-trace btn btn-sm btn-outline-info text-white">
                                                    Backtrace
                                                </button>
                                                <button class="btn-code btn btn-sm btn-outline-danger text-white"
                                                        data-index="<?= $index ?>">Code
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <p>
                                        <strong class="text-muted">Agruments:</strong>
                                        <small><?= $item['arguments'] ?? '' ?> </small>
                                    </p>
                                    <p>
                                        <strong class="text-muted">Options:</strong>
                                        <small><?= $item['options'] ?? '' ?></small>
                                    </p>
                                </div>
                                <ol class="bt hidden">
                                    <?php foreach ($item['backtrace'] ?? [] as $trace) : ?>
                                        <li>
                                            <div class="bg-secondary text-light small">
                                                <?= $trace['file'] ?? '' ?>:<?= $trace['line'] ?? '' ?>
                                                <a target="_blank" href="<?= $trace['editorHref'] ?? '' ?>">#</a>
                                            </div>
                                            <div class="text-muted small">
                                                <?= is_object($trace['class'] ?? '') ? get_class($trace['class']) : $trace['class'] ?><?= $trace['type'] ?? '' ?><?= $trace['function'] ?? '' ?><?php if (is_array($trace['object'])): ?>
                                                    (<?= implode(', ', array_map(function ($v, $k) {
                                                        return sprintf("%s='%s'", $k, $v);
                                                    }, $trace['object'], array_keys($trace['object']))); ?>)
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>