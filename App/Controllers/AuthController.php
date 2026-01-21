<?php

//Vytvorené s pomocou Github Copilot

namespace App\Controllers;

use App\Configuration;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\ViewResponse;
use App\Models\UserRepository;
use App\Models\User;

/**
 * Class AuthController
 *
 * This controller handles authentication actions such as login, logout, and redirection to the login page. It manages
 * user sessions and interactions with the authentication system.
 *
 * @package App\Controllers
 */
class AuthController extends BaseController
{
    /**
     * Redirects to the login page.
     *
     * This action serves as the default landing point for the authentication section of the application, directing
     * users to the login URL specified in the configuration.
     *
     * @return Response The response object for the redirection to the login page.
     */
    public function index(Request $request): Response
    {
        return $this->redirect(Configuration::LOGIN_URL);
    }

    /**
     * Authenticates a user and processes the login request.
     *
     * This action handles user login attempts. If the login form is submitted, it attempts to authenticate the user
     * with the provided credentials. Upon successful login, the user is redirected to the admin dashboard.
     * If authentication fails, an error message is displayed on the login page.
     *
     * @return Response The response object which can either redirect on success or render the login view with
     *                  an error message on failure.
     * @throws Exception If the parameter for the URL generator is invalid throws an exception.
     */
    public function login(Request $request): Response
    {
        $logged = null;
        if ($request->hasValue('submit')) {
            // use email instead of username
            $logged = $this->app->getAuthenticator()->login($request->value('email'), $request->value('password'));
            if ($logged) {
                return $this->redirect($this->url("admin.index"));
            }
        }

        $message = $logged === false ? 'Bad email or password' : null;
        return $this->html(compact("message"));
    }

    /**
     * Register a new user: validate input, check duplicate email, insert into DB and redirect to login.
     *
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function register(Request $request): Response
    {
        $errors = [];
        $email = '';

        if ($request->hasValue('submit')) {
            $email = trim((string)$request->value('email'));
            $password = (string)$request->value('password');
            $confirm = (string)$request->value('confirm_password');

            // Required fields
            if ($email === '' || $password === '' || $confirm === '') {
                $errors[] = 'All fields are required.';
            }

            // Email format
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Email is not valid.';
            }

            // Password length
            if ($password !== '' && strlen($password) < 6) {
                $errors[] = 'Password must be at least 6 characters long.';
            }

            // Passwords match
            if ($password !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }

            if (empty($errors)) {
                try {
                    // Check duplicate email via repository
                    $existing = UserRepository::findByEmail($email);
                    if ($existing) {
                        $errors[] = 'Email is already registered.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $displayName = strtok($email, '@');
                        UserRepository::create($email, $hash, $displayName);

                        // Redirect to login page after successful registration
                        return $this->redirect(Configuration::LOGIN_URL);
                    }
                } catch (Exception $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }

        // Pass back the email so the view can prefill it when there are validation errors
        return $this->html(compact('errors', 'email'));
    }

    /**
     * Logs out the current user.
     *
     * This action terminates the user's session and redirects them to a view. It effectively clears any authentication
     * tokens or session data associated with the user.
     *
     * @return ViewResponse The response object that renders the logout view.
     */
    public function logout(Request $request): Response
    {
        $this->app->getAuthenticator()->logout();
        return $this->redirect($this->app->getLinkGenerator()->url('home.index'));
    }

    /**
     * Display settings page and handle change password requests.
     *
     * Form fields (POST): old_password, password, confirm_password, submit
     */
    public function settings(Request $request): Response
    {
        // Only allow logged-in users to access settings
        if (!$this->user->isLoggedIn()) {
            return $this->redirect(Configuration::LOGIN_URL);
        }

        $errors = [];
        $success = null;
        // current display name to prefill the form
        $displayName = $this->user->getName();

        if ($request->hasValue('submit')) {
            // Read display name (always present in the form)
            $displayName = trim((string)$request->value('display_name'));

            $old = (string)$request->value('old_password');
            $new = (string)$request->value('password');
            $confirm = (string)$request->value('confirm_password');

            // Display name validation
            if ($displayName === '') {
                $errors[] = 'Display name is required.';
            } elseif (strlen($displayName) > 100) {
                $errors[] = 'Display name must be at most 100 characters.';
            }

            // If user provided any password fields, validate password change inputs
            $passwordAttempted = ($old !== '' || $new !== '' || $confirm !== '');
            if ($passwordAttempted) {
                if ($old === '' || $new === '' || $confirm === '') {
                    $errors[] = 'All password fields are required to change the password.';
                }

                if ($new !== '' && strlen($new) < 6) {
                    $errors[] = 'New password must be at least 6 characters long.';
                }

                if ($new !== $confirm) {
                    $errors[] = 'New passwords do not match.';
                }
            }

            if (empty($errors)) {
                try {
                    $email = $this->user->getEmail();

                    $changed = false; // track whether we updated anything

                    // Handle password change if requested
                    if ($passwordAttempted) {
                        $row = UserRepository::findByEmail($email);
                        $hash = $row['password_hash'] ?? null;

                        if (!$hash || !password_verify($old, $hash)) {
                            $errors[] = 'Old password is incorrect.';
                        } else {
                            $newHash = password_hash($new, PASSWORD_DEFAULT);
                            UserRepository::updatePasswordByEmail($email, $newHash);
                            $changed = true;
                            $success = 'Password changed successfully.';
                        }
                    }

                    // Handle display name update (if changed)
                    if ($displayName !== $this->user->getName()) {
                        UserRepository::updateDisplayNameByEmail($email, $displayName);

                        // Replace identity in session so the new display name is used immediately
                        $newIdentity = new User($this->user->getId(), $this->user->getEmail(), $displayName);
                        $this->app->getSession()->set(Configuration::IDENTITY_SESSION_KEY, $newIdentity);

                        // Refresh controller user reference so layout and subsequent calls in this request see the new name
                        $this->user = $this->app->getAppUser();

                        $changed = true;

                        // If we didn't already set a success message from password change, set one for name
                        if ($success === null) {
                            $success = 'Display name updated successfully.';
                        } else {
                            // combine messages
                            $success .= ' Display name updated.';
                        }
                    }

                    // If we changed something and there are no errors, redirect to the user menu to avoid history issues
                    if ($changed && empty($errors)) {
                        // store a flash message in session so admin.index can show confirmation
                        $flashMsg = $success ?? 'Settings updated.';
                        $this->app->getSession()->set('flash_message', $flashMsg);
                        return $this->redirect($this->url('admin.index'));
                    }

                } catch (\Exception $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }

        return $this->html(compact('errors', 'success', 'displayName'));
    }
}
