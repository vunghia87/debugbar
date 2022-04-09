<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between"><h5>Request</h5>
        <input type="text" id="searchInput" placeholder="Search Tag" class="form-control w-25">
    </div>
    <table id="indexScreen" class="table table-hover table-sm mb-0 penultimate-column-right">
        <thead>
        <tr>
            <th scope="col">Method</th>
            <th scope="col">Uri</th>
            <th scope="col">Action</th>
            <th scope="col">Status</th>
            <th scope="col">Happened</th>
            <th scope="col"></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $item) : ?>
            <tr>
                <td class="pr-0">
                    <span class="badge font-weight-light badge-secondary"><?= $item['__meta']['method'] ?? '' ?></span>
                </td>
                <td title="<?= $item['__meta']['uri'] ?? '' ?>"><?= $item['__meta']['uri'] ?? '' ?></td>
                <td><?= $item['__meta']['action'] ?? '' ?></td>
                <td>
                    <span class="badge font-weight-light badge-secondary"><?= $item['response']['status_code'] ?? 200 ?></span>
                </td>
                <td title="<?= $item['__meta']['datetime'] ?? '' ?>" class="table-fit"><?= time_human($item['__meta']['datetime'] ?? 0) ?></td>
                <td class="table-fit">
                    <a href="?openPhpDebugBar=true&op=detail&id=<?= $item['__meta']['id'] ?? '' ?>" class="control-action">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 22 16">
                            <path d="M16.56 13.66a8 8 0 0 1-11.32 0L.3 8.7a1 1 0 0 1 0-1.42l4.95-4.95a8 8 0 0 1 11.32 0l4.95 4.95a1 1 0 0 1 0 1.42l-4.95 4.95-.01.01zm-9.9-1.42a6 6 0 0 0 8.48 0L19.38 8l-4.24-4.24a6 6 0 0 0-8.48 0L2.4 8l4.25 4.24h.01zM10.9 12a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm0-2a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"></path>
                        </svg>
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>