<div class="card ml-auto mr-3">
    <div class="card-header d-flex align-items-center justify-content-between p-0">
        <ul class="nav nav-pills">
            <li class="nav-item"><a href="#request" class="nav-link">Request</a></li>
            <li class="nav-item"><a href="#response" class="nav-link">Response</a></li>

            <?php if (!empty($data['auth'])): ?>
                <li class="nav-item"><a href="#auth" class="nav-link">Auth</a></li>
            <?php endif; ?>

            <?php if (!empty($data['exceptions']['count'])): ?>
                <li class="nav-item">
                    <a href="#exception" class="nav-link">
                        Exception (<?= $data['exceptions']['count'] ?>)
                    </a>
                </li>
            <?php endif; ?>
            <?php if (!empty($data['pdo']['statements'])): ?>
                <li class="nav-item">
                    <a href="#pdo" class="nav-link">
                        Queries (<?= count($data['pdo']['statements']) ?>)
                    </a>
                </li>
            <?php endif; ?>
            <?php if (!empty($data['messages']['count'])): ?>
                <li class="nav-item">
                    <a href="#message" class="nav-link">
                        Message (<?= $data['messages']['count'] ?>)
                    </a>
                </li>
            <?php endif; ?>
            <?php if (!empty($data['memcache']['count'])): ?>
                <li class="nav-item">
                    <a href="#memcache" class="nav-link">
                        Memcache (<?= $data['memcache']['count'] ?>)
                    </a>
                </li>
            <?php endif; ?>
            <?php if (!empty($data['command']['count'])): ?>
                <li class="nav-item">
                    <a href="#command" class="nav-link">
                        Commmand (<?= $data['command']['count'] ?>)
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</div>