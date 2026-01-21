<?php

//Vytvorené s pomocou Github Copilot

namespace App\Models;

use Framework\DB\Connection;
use Framework\Http\UploadedFile;

class Plant
{
    public static function getForUser(int $userId, string $sort = 'name_asc'): array
    {
        $db = Connection::getInstance();
        $orderBy = 'p.common_name ASC';
        switch ($sort) {
            case 'name_desc':
                $orderBy = 'p.common_name DESC';
                break;
            case 'date_purchased':
                $orderBy = 'p.purchase_date ASC, p.common_name ASC';
                break;
            case 'scientific_name':
                $orderBy = "(CASE WHEN p.scientific_name IS NULL OR p.scientific_name = '' THEN 1 ELSE 0 END), p.scientific_name ASC, p.common_name ASC";
                break;
            case 'name_asc':
            default:
                $orderBy = 'p.common_name ASC';
        }

        $sql = "SELECT p.plant_id, p.common_name, (
                SELECT i.file_path FROM images i WHERE i.plant_id = p.plant_id LIMIT 1
            ) AS file_path
            FROM plants p
            WHERE p.user_id = ?
            ORDER BY " . $orderBy;

        $stmt = $db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function create(int $userId, array $data, ?UploadedFile $uploaded = null): int
    {
        $db = Connection::getInstance();

        // generate plant_uid
        $timestamp = (new \DateTime())->format('YmdHis');
        $uniq = substr(uniqid(), -6);
        $plant_uid = $timestamp . '_' . $userId . '_' . $uniq;

        $stmt = $db->prepare('INSERT INTO plants (plant_uid, user_id, common_name, scientific_name, location, purchase_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $plant_uid,
            $userId,
            $data['common_name'] ?? '',
            $data['scientific_name'] ?? '',
            $data['location'] ?? '',
            $data['purchase_date'] ?? null,
            $data['notes'] ?? ''
        ]);

        $plantId = (int)$db->lastInsertId();

        if ($uploaded && method_exists($uploaded, 'isOk') && $uploaded->isOk()) {
            $webPath = Image::store($plantId, $uploaded);
            if ($webPath !== null) {
                $stmtImg = $db->prepare('INSERT INTO images (plant_id, file_path) VALUES (?, ?)');
                $stmtImg->execute([$plantId, $webPath]);
            }
        }

        return $plantId;
    }

    public static function getById(int $plantId): ?array
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('SELECT * FROM plants WHERE plant_id = ?');
        $stmt->execute([$plantId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function update(int $plantId, int $userId, array $data, ?UploadedFile $uploaded = null): array
    {
        $db = Connection::getInstance();

        $stmt = $db->prepare('UPDATE plants SET common_name = ?, scientific_name = ?, location = ?, purchase_date = ?, notes = ? WHERE plant_id = ? AND user_id = ?');
        $stmt->execute([
            $data['common_name'] ?? '',
            $data['scientific_name'] ?? '',
            $data['location'] ?? '',
            $data['purchase_date'] ?? null,
            $data['notes'] ?? '',
            $plantId,
            $userId
        ]);

        $oldFiles = [];
        if ($uploaded && method_exists($uploaded, 'isOk') && $uploaded->isOk()) {
            // fetch existing images
            $oldFiles = Image::getImagesByPlant($plantId);
            Image::deleteImagesByPlant($plantId); // remove DB entries

            $webPath = Image::store($plantId, $uploaded);
            if ($webPath !== null) {
                $stmtImg = $db->prepare('INSERT INTO images (plant_id, file_path) VALUES (?, ?)');
                $stmtImg->execute([$plantId, $webPath]);
            }
        }

        return $oldFiles;
    }

    public static function delete(int $plantId, int $userId): array
    {
        $db = Connection::getInstance();
        // Verify ownership
        $stmt = $db->prepare('SELECT user_id FROM plants WHERE plant_id = ?');
        $stmt->execute([$plantId]);
        $row = $stmt->fetch();
        if (!$row) {
            return [];
        }
        if ((int)$row['user_id'] !== $userId) {
            return [];
        }

        $db->beginTransaction();
        try {
            // gather image paths
            $images = Image::getImagesByPlant($plantId);

            $stmt = $db->prepare('DELETE FROM images WHERE plant_id = ?');
            $stmt->execute([$plantId]);

            $stmt = $db->prepare('DELETE FROM reminders WHERE plant_id = ?');
            $stmt->execute([$plantId]);

            $stmt = $db->prepare('DELETE FROM plants WHERE plant_id = ?');
            $stmt->execute([$plantId]);

            $db->commit();
            return $images;
        } catch (\Exception $e) {
            try { $db->rollBack(); } catch (\Exception $_) {}
            return [];
        }
    }
}

