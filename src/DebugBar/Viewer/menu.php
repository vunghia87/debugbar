<div class="card ml-auto mr-3">
    <div class="card-header d-flex align-items-center justify-content-between p-0">
        <ul class="nav nav-pills">
            <li class="nav-item"><a href="#request" class="nav-link">Request</a></li>
            <li class="nav-item"><a href="#response" class="nav-link">Response</a></li>
            <li class="nav-item"><a href="#auth" class="nav-link">Auth</a></li>
            <li class="nav-item"><a href="#exception" class="nav-link">Exception <?= !empty($data['exceptions']['count']) ? "(".$data['exceptions']['count'].")" : "(0)" ?></a></li>
            <li class="nav-item"><a href="#database" class="nav-link">Database <?= !empty($data['pdo']['statements']) ? "(".count($data['pdo']['statements']).")" : "(0)" ?></a></li>
            <li class="nav-item"><a href="#message" class="nav-link">Message <?= !empty($data['messages']['count']) ? "(".$data['messages']['count'].")" : "(0)" ?></a></li>
            <li class="nav-item"><a href="#memcache" class="nav-link">Memcache <?= !empty($data['memcache']['count']) ? "(".count($data['memcache']['count']).")" : "(0)" ?></a></li>
            <li class="nav-item"><a href="#command" class="nav-link">Command <?= !empty($data['command']['count']) ? "(".$data['command']['count'].")" : "(0)" ?></a></li>
        </ul>
    </div>
</div>