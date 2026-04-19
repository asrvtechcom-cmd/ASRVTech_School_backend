<?php

declare(strict_types=1);

namespace App\Utils;

use Exception;

class MediaService
{
    /**
     * Upload a file to Cloudinary using the Unsigned Upload Preset.
     * This bypasses the need for an API Secret and provides permanent storage.
     */
    public static function uploadToCloudinary(array $file): ?string
    {
        $cloudName = getenv('CLOUDINARY_CLOUD_NAME');
        $uploadPreset = getenv('CLOUDINARY_UPLOAD_PRESET');

        if (!$cloudName || !$uploadPreset) {
            error_log("MediaService Error: Cloudinary settings are not configured in environment.");
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            error_log("MediaService Error: File upload error code " . $file['error']);
            return null;
        }

        // 1. Prepare the API endpoint
        $url = "https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload";

        // 2. Prepare the payload (Unsigned upload requires the upload_preset)
        $data = [
            'file' => new \CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'upload_preset' => $uploadPreset,
        ];

        // 3. Send using CURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $decoded = json_decode((string) $response, true);
            // Return the secure URL provided by Cloudinary
            return $decoded['secure_url'] ?? null;
        }

        error_log("Cloudinary API Error (HTTP $httpCode): " . $response . " | " . $curlError);
        return null;
    }
}
