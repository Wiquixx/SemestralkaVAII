<?php

//Vytvorené s pomocou Github Copilot

namespace App\Models;

use Framework\DB\Connection;

class UserRepository
{
    public static function listAllWithCounts(): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT u.user_id, u.display_name, u.email, u.created_at,
            COALESCE(p.plant_count, 0) AS plant_count,
            COALESCE(r.reminder_count, 0) AS reminder_count
            FROM users u
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS plant_count FROM plants GROUP BY user_id
            ) p ON p.user_id = u.user_id
            LEFT JOIN (
                SELECT user_id, COUNT(*) AS reminder_count FROM reminders GROUP BY user_id
            ) r ON r.user_id = u.user_id
            ORDER BY u.user_id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute([]);
        return $stmt->fetchAll();
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $email, string $passwordHash, string $displayName): int
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('INSERT INTO users (email, password_hash, display_name) VALUES (?, ?, ?)');
        $stmt->execute([$email, $passwordHash, $displayName]);
        return (int)$db->lastInsertId();
    }

    public static function updatePasswordByEmail(string $email, string $passwordHash): bool
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE email = ?');
        $stmt->execute([$passwordHash, $email]);
        return true;
    }

    public static function updateDisplayNameByEmail(string $email, string $displayName): bool
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('UPDATE users SET display_name = ? WHERE email = ?');
        $stmt->execute([$displayName, $email]);
        return true;
    }

    public static function getById(int $id): ?array
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

