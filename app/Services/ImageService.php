<?php

namespace App\Services;

class ImageService
{
    private const MAX_WIDTH = 800;
    private const JPEG_QUALITY = 85;
    private const SUPPORTED_TYPES = [
        IMAGETYPE_JPEG => 'jpeg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp'
    ];

    public function optimizeImage(string $sourcePath): string
    {
        if (!file_exists($sourcePath)) {
            throw new \Exception("Le fichier image n'existe pas : " . $sourcePath);
        }

        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            throw new \Exception("Impossible de lire les informations de l'image");
        }

        [$width, $height, $type] = $imageInfo;

        if (!isset(self::SUPPORTED_TYPES[$type])) {
            throw new \Exception('Format d\'image non supporté');
        }

        $ratio = $width / $height;
        $newWidth = min($width, self::MAX_WIDTH);
        $newHeight = (int)($newWidth / $ratio);

        $newImage = imagecreatetruecolor($newWidth, $newHeight);

        if ($type === IMAGETYPE_PNG || $type === IMAGETYPE_WEBP) {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }

        $sourceImage = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            default => throw new \Exception('Format non supporté'),
        };

        imagecopyresampled(
            $newImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $newWidth,
            $newHeight,
            $width,
            $height
        );

        ob_start();
        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($newImage, null, self::JPEG_QUALITY),
            IMAGETYPE_PNG => imagepng($newImage),
            IMAGETYPE_WEBP => imagewebp($newImage),
        };
        $imageData = ob_get_clean();

        imagedestroy($sourceImage);
        imagedestroy($newImage);

        $extension = self::SUPPORTED_TYPES[$type];
        return "data:image/{$extension};base64," . base64_encode($imageData);
    }
}
