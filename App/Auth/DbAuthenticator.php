<?php

//Vytvorené s pomocou Github Copilot

namespace App\Auth;

use App\Models\User;
use Framework\Auth\SessionAuthenticator;
use Framework\Core\IIdentity;
use Framework\Core\App;
use Framework\DB\Connection;

/**
 * Class DbAuthenticator
 * Authenticates users against the `users` database table.
 */
class DbAuthenticator extends SessionAuthenticator
{
    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    // Keep parameter name 'username' to match the abstract signature in SessionAuthenticator
    protected function authenticate(string $username, string $password): ?IIdentity
    {
        // In this app 'username' is actually the user's email address
        $email = $username;

        $db = Connection::getInstance();
        $stmt = $db->prepare('SELECT user_id, email, password_hash, display_name, status FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        if ($row) {
            $hash = $row['password_hash'] ?? $row['password'] ?? null; // be flexible if different column name
            if ($hash && password_verify($password, $hash)) {
                return new User((int)$row['user_id'], $row['email'], $row['display_name'], isset($row['status']) ? (int)$row['status'] : 2);
            }
        }
        return null;
    }
}
