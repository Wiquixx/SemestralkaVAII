<?php

/** @var array|null $errors */
/** @var string|null $success */
/** @var string|null $email */
/** @var \Framework\Support\LinkGenerator $link */
/** @var \Framework\Support\View $view */

$view->setLayout('auth');
?>

<div class="container">
    <div class="row">
        <div class="col-sm-9 col-md-7 col-lg-5 mx-auto">
            <div class="card card-signin my-5">
                <div class="card-body">
                    <h5 class="card-title text-center">Register</h5>

                    <?php if (!empty($errors)) { ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err) { ?>
                                    <li><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></li>
                                <?php } ?>
                            </ul>
                        </div>
                    <?php } ?>

                    <?php if (!empty($success)) { ?>
                        <div class="alert alert-success text-center">
                            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    <?php } ?>

                    <form class="form-signin" method="post" action="<?= $link->url('register') ?>">
                        <div class="form-label-group mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input name="email" type="email" id="email" class="form-control" placeholder="Email" required autofocus value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <!-- Password input with toggle -->
                        <div class="form-label-group mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <input name="password" type="password" id="password" class="form-control" placeholder="Password" required aria-describedby="togglePassword">
                                <button class="btn btn-outline-secondary pwd-toggle" type="button" id="togglePassword" data-target="password" aria-pressed="false" aria-label="Show password">
                                    <!-- eye-slash (initial: hidden) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M13.359 11.238 15 12.879 13.879 14 2 2.121 3.121 1 6.042 3.921A7.941 7.941 0 0 1 8 3c3.523 0 6.5 2.167 8 5-1.002 1.87-2.72 3.331-3.641 3.238z"/>
                                        <path d="M11.646 9.146a3 3 0 0 1-4.292-4.292l4.292 4.292z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm password input with toggle -->
                        <div class="form-label-group mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <input name="confirm_password" type="password" id="confirm_password" class="form-control" placeholder="Confirm Password" required aria-describedby="toggleConfirmPassword">
                                <button class="btn btn-outline-secondary pwd-toggle" type="button" id="toggleConfirmPassword" data-target="confirm_password" aria-pressed="false" aria-label="Show confirm password">
                                    <!-- eye-slash (initial: hidden) -->
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">
                                        <path d="M13.359 11.238 15 12.879 13.879 14 2 2.121 3.121 1 6.042 3.921A7.941 7.941 0 0 1 8 3c3.523 0 6.5 2.167 8 5-1.002 1.87-2.72 3.331-3.641 3.238z"/>
                                        <path d="M11.646 9.146a3 3 0 0 1-4.292-4.292l4.292 4.292z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center gap-2">
                            <button class="btn btn-primary" type="submit" name="submit">Register</button>
                            <a class="btn btn-secondary" href="<?= $link->url('home.index') ?>">Cancel</a>
                        </div>
                    </form>

                    <!-- Inline script to toggle password visibility for both fields -->
                    <script>
                        (function(){
                            function eyeOpenSvg() {
                                return '\n<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">\n  <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/>\n  <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z" fill="#fff"/>\n</svg>';
                            }
                            function eyeSlashSvg() {
                                return '\n<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">\n  <path d="M13.359 11.238 15 12.879 13.879 14 2 2.121 3.121 1 6.042 3.921A7.941 7.941 0 0 1 8 3c3.523 0 6.5 2.167 8 5-1.002 1.87-2.72 3.331-3.641 3.238z"/>\n  <path d="M11.646 9.146a3 3 0 0 1-4.292-4.292l4.292 4.292z"/>\n</svg>';
                            }

                            document.querySelectorAll('.pwd-toggle').forEach(function(btn){
                                var targetId = btn.getAttribute('data-target');
                                var input = document.getElementById(targetId);
                                // initialize with eye-slash icon (hidden)
                                btn.innerHTML = eyeSlashSvg();
                                btn.addEventListener('click', function(){
                                    if (!input) return;
                                    if (input.type === 'password') {
                                        input.type = 'text';
                                        btn.innerHTML = eyeOpenSvg();
                                        btn.setAttribute('aria-pressed','true');
                                        btn.setAttribute('aria-label','Hide password');
                                    } else {
                                        input.type = 'password';
                                        btn.innerHTML = eyeSlashSvg();
                                        btn.setAttribute('aria-pressed','false');
                                        btn.setAttribute('aria-label','Show password');
                                    }
                                });
                            });
                        })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</div>
