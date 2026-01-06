<?php

namespace App\Controllers;

use App\Configuration;
use Exception;
use Framework\Core\BaseController;
use Framework\Http\Request;
use Framework\Http\Responses\Response;
use Framework\Http\Responses\ViewResponse;
use Framework\DB\Connection;

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
                    $db = Connection::getInstance();
                    // Check duplicate email
                    $check = $db->prepare('SELECT user_id FROM users WHERE email = ?');
                    $check->execute([$email]);
                    $existing = $check->fetch();
                    if ($existing) {
                        $errors[] = 'Email is already registered.';
                    } else {
                        $hash = password_hash($password, PASSWORD_DEFAULT);
                        $displayName = strtok($email, '@');
                        $insert = $db->prepare('INSERT INTO users (email, password_hash, display_name) VALUES (?, ?, ?)');
                        $insert->execute([$email, $hash, $displayName]);

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
}
