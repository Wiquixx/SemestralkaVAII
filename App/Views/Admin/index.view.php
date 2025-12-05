<?php

/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Auth\AppUser $user */
?>

<div class="container-fluid">
    <div class="row">
        <div class="col text-center">
            <div>
                Welcome, <strong><?= $user->getName() ?></strong>!<br><br>
                <span>This part of the application is accessible only after logging in.</span>
            </div>
            <div class="admin-actions-grid mt-5">
                <a href="#" class="admin-action-btn">View Calendar</a>
                <a href="#" class="admin-action-btn">Create Schedule</a>
                <a href="#" class="admin-action-btn">Add Plant</a>
                <a href="#" class="admin-action-btn">Remove Plant</a>
            </div>
        </div>
    </div>
</div>