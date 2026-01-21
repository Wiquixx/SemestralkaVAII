<?php

//Vytvorené s pomocou Github Copilot

/** @var array|null $errors */
/** @var string|null $success */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container">
    <div class="row">
        <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
            <div class="card my-5">
                <div class="card-body">
                    <h5 class="card-title text-center">Settings - Change Password</h5>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $e): ?>
                                    <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>

                    <form method="post" action="<?= $link->url('auth.settings') ?>">
                        <!-- Display name -->
                        <div class="mb-3">
                            <label for="display_name" class="form-label">Display name</label>
                            <input name="display_name" type="text" id="display_name" class="form-control" required value="<?= htmlspecialchars($displayName ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <!-- Current password with toggle -->
                        <div class="mb-3">
                            <label for="old_password" class="form-label">Current password</label>
                            <div class="input-group">
                                <input name="old_password" type="password" id="old_password" class="form-control" aria-describedby="toggleOldPassword">
                                <button class="btn btn-outline-secondary pwd-toggle" type="button" id="toggleOldPassword" data-target="old_password" aria-pressed="false" aria-label="Show current password"></button>
                            </div>
                        </div>

                        <!-- New password with toggle -->
                        <div class="mb-3">
                            <label for="password" class="form-label">New password</label>
                            <div class="input-group">
                                <input name="password" type="password" id="password" class="form-control" aria-describedby="togglePassword">
                                <button class="btn btn-outline-secondary pwd-toggle" type="button" id="togglePassword" data-target="password" aria-pressed="false" aria-label="Show new password"></button>
                            </div>
                        </div>

                        <!-- Confirm new password with toggle -->
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm new password</label>
                            <div class="input-group">
                                <input name="confirm_password" type="password" id="confirm_password" class="form-control" aria-describedby="toggleConfirmPassword">
                                <button class="btn btn-outline-secondary pwd-toggle" type="button" id="toggleConfirmPassword" data-target="confirm_password" aria-pressed="false" aria-label="Show confirm password"></button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-primary" type="submit" name="submit">Confirm</button>
                            <a class="btn btn-secondary" href="<?= $link->url('admin.index') ?>">Back</a>
                        </div>
                    </form>

                    <script src="<?= htmlspecialchars($link->asset('js/password_toggle.js'), ENT_QUOTES) ?>"></script>
                </div>
            </div>
        </div>
    </div>
</div>
