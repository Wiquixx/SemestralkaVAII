<?php

namespace App\Models;

use Framework\DB\Connection;
use Framework\Http\UploadedFile;

class Image
{
    public static function store(int $plantId, UploadedFile $uploaded): ?string
    {
        $maxBytes = 5 * 1024 * 1024;
        if ($uploaded->getSize() > $maxBytes) {
            throw new \Exception('Uploaded image exceeds 5MB size limit.');
        }

        $tmpPath = $uploaded->getFileTempPath();
        $finfoType = @mime_content_type($tmpPath);
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($finfoType, $allowed, true)) {
            throw new \Exception('Uploaded file is not a permitted image type (JPEG, PNG, GIF).');
        }

        $projectRoot = dirname(__DIR__, 2);
        $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $origName = $uploaded->getName();
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if ($ext === '') {
            $map = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
            $ext = $map[$finfoType] ?? 'bin';
        }

        $newFileName = 'plant_' . $plantId . '_' . substr(uniqid(), -8) . '.' . $ext;
        $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $newFileName;

        if (!$uploaded->store($targetPath)) {
            throw new \Exception('Failed to move uploaded file.');
        }

        return '/uploads/' . $newFileName;
    }

    public static function getImagesByPlant(int $plantId): array
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('SELECT file_path FROM images WHERE plant_id = ?');
        $stmt->execute([$plantId]);
        return $stmt->fetchAll();
    }

    public static function deleteImagesByPlant(int $plantId): void
    {
        $db = Connection::getInstance();
        $stmt = $db->prepare('DELETE FROM images WHERE plant_id = ?');
        $stmt->execute([$plantId]);
    }

    public static function unlinkFiles(array $images): void
    {
        $projectRoot = dirname(__DIR__, 2);
        foreach ($images as $img) {
            $file = trim((string)($img['file_path'] ?? ''));
            if ($file === '') continue;
            $filePath = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $file), DIRECTORY_SEPARATOR);
            try { if (is_file($filePath)) { @unlink($filePath); } } catch (\Exception $_) {}
        }
    }
}

