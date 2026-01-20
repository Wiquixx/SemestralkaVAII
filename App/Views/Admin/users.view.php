<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser $user */
/** @var array $users */
?>
<div class="container">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <h2 class="mb-0">List of users</h2>
        <a href="<?= $link->url('admin.index') ?>" class="btn btn-secondary btn-sm">&larr; Back to menu</a>
    </div>

    <?php if (empty($users)): ?>
        <div class="text-muted">No users found.</div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table table-striped table-sm">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Date Created</th>
                    <th>Number of Plants</th>
                    <th>Number of Plans</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= (int)($u['user_id'] ?? 0) ?></td>
                        <td><?= htmlspecialchars($u['display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($u['email'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($u['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int)($u['plant_count'] ?? 0) ?></td>
                        <td><?= (int)($u['reminder_count'] ?? 0) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
