<?php

namespace App\Models;

use Framework\DB\Connection;

class Reminder
{
    public static function insert(array $data): int
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('INSERT INTO reminders (user_id, plant_id, remind_date, frequency_days, title, notes, active) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $data['user_id'],
            $data['plant_id'],
            $data['remind_date'],
            $data['frequency_days'],
            $data['title'],
            $data['notes'] ?? '',
            $data['active'] ?? 1
        ]);
        return (int)$db->lastInsertId();
    }

    public static function getById(int $id): ?array
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('SELECT * FROM reminders WHERE reminder_id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('UPDATE reminders SET plant_id = ?, remind_date = ?, frequency_days = ?, title = ?, notes = ? WHERE reminder_id = ? AND user_id = ?');
        $stmt->execute([
            $data['plant_id'],
            $data['remind_date'],
            $data['frequency_days'],
            $data['title'],
            $data['notes'] ?? '',
            $id,
            $userId
        ]);
        return true;
    }

    public static function delete(int $id): void
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('DELETE FROM reminders WHERE reminder_id = ?');
        $stmt->execute([$id]);
    }

    public static function listForUser(int $userId): array
    {
        $db = Connection::getInstance();
        $sql = "SELECT r.reminder_id, r.user_id, r.plant_id, r.remind_date, r.frequency_days, r.title, r.notes, p.common_name AS plant_name
                FROM reminders r
                LEFT JOIN plants p ON p.plant_id = r.plant_id
                WHERE r.user_id = ?
                ORDER BY r.remind_date DESC, r.reminder_id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function occurrencesForMonth(int $userId, int $year, int $month): array
    {
        $start = new \DateTime(sprintf('%04d-%02d-01', $year, $month));
        $end = clone $start;
        $end->modify('last day of this month');

        $db = Connection::getInstance();
        $sql = "SELECT r.reminder_id, r.user_id, r.plant_id, r.remind_date, r.frequency_days, r.title, r.notes, p.common_name AS plant_name
                    FROM reminders r
                    JOIN plants p ON p.plant_id = r.plant_id
                    WHERE r.user_id = ?
                      AND r.active = 1
                      AND (
                        (r.frequency_days = -1 AND r.remind_date BETWEEN ? AND ?)
                        OR (r.frequency_days > 0 AND r.remind_date <= ?)
                      )";
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $start->format('Y-m-d'), $end->format('Y-m-d'), $end->format('Y-m-d')]);
        $rows = $stmt->fetchAll();

        $occurrences = [];
        foreach ($rows as $r) {
            $freq = (int)($r['frequency_days'] ?? -1);
            $first = new \DateTime($r['remind_date']);
            if ($freq === -1) {
                $dStr = $first->format('Y-m-d');
                $occurrences[] = [
                    'date' => $dStr,
                    'reminder_id' => (int)$r['reminder_id'],
                    'title' => $r['title'],
                    'notes' => $r['notes'],
                    'plant_id' => (int)$r['plant_id'],
                    'plant_name' => $r['plant_name'] ?? null,
                ];
            } elseif ($freq > 0) {
                if ($first > $end) continue;

                $d = clone $first;
                if ($d < $start) {
                    $diff = (int)(($start->getTimestamp() - $d->getTimestamp()) / 86400);
                    $steps = (int)floor($diff / $freq);
                    if ($steps > 0) { $d->modify('+' . ($steps * $freq) . ' days'); }
                    while ($d < $start) { $d->modify('+' . $freq . ' days'); }
                }
                while ($d <= $end) {
                    $occurrences[] = [
                        'date' => $d->format('Y-m-d'),
                        'reminder_id' => (int)$r['reminder_id'],
                        'title' => $r['title'],
                        'notes' => $r['notes'],
                        'plant_id' => (int)$r['plant_id'],
                        'plant_name' => $r['plant_name'] ?? null,
                    ];
                    $d->modify('+' . $freq . ' days');
                }
            }
        }

        return array_values($occurrences);
    }
}

