<?php

//Vytvorené s pomocou Github Copilot

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
    private int $status; // 1=admin, 2=user

    public function __construct(int $id, string $email, string $displayName, int $status = 2)
    {
        $this->id = $id;
        $this->email = $email;
        $this->displayName = $displayName;
        $this->status = $status;
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

    /**
     * Get numeric status of the user (1 = admin, 2 = user)
     * Defensive: if the typed property wasn't initialized (e.g. older serialized object), return default 2.
     */
    public function getStatus(): int
    {
        // isset() will return false if the typed property hasn't been initialized, avoiding a TypeError on access.
        return isset($this->status) ? $this->status : 2;
    }
}
