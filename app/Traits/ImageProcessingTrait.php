<?php

namespace App\Traits;

use App\Services\ImageService;

trait ImageProcessingTrait
{
    protected ImageService $imageService;

    public function setImageService(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    protected function processImageInput(string $image): string
    {
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            return $image;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $image)) {
            return $image;
        }

        if (!file_exists($image)) {
            throw new \Exception("Le fichier image n'existe pas : " . $image);
        }

        try {
            if (!isset($this->imageService)) {
                throw new \Exception("ImageService n'a pas été injecté dans la classe utilisant ce Trait.");
            }

            return $this->imageService->optimizeImage($image);
        } catch (\Exception $e) {
            logger()->error("Erreur lors du traitement de l'image", [
                'error' => $e->getMessage(),
                'file' => $image
            ]);
            throw $e;
        }
    }
}