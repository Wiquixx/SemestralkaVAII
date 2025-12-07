<?php

namespace App\Models;

use Framework\Core\IIdentity;

/**
 * Class User
 * Represents a user stored in the database and implements IIdentity for use with the framework authenticator.
 */
class User implements IIdentity
{
    private int $id;
    private string $email;
    private string $displayName;

    public function __construct(int $id, string $email, string $displayName)
    {
        $this->id = $id;
        $this->email = $email;
        $this->displayName = $displayName;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getName(): string
    {
        return $this->displayName;
    }
}

