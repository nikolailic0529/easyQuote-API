<?php

namespace App\Traits\Image;

use App\Services\ThumbnailManager;
use Illuminate\Support\Str;

trait HasLogo
{
    public function createLogo($file, $fake = false)
    {
        return ThumbnailManager::createLogoThumbnails($this, $file, $fake);
    }

    public function thumbnailProperties(): array
    {
        return [
            'x1' => [
                'width' => 60,
                'height' => 30
            ],
            'x2' => [
                'width' => 120,
                'height' => 60
            ],
            'x3' => [
                'width' => 240,
                'height' => 120
            ]
        ];
    }

    public function imagesDirectory(): string
    {
        $name = Str::snake(Str::plural(class_basename($this)));
        return "images/{$name}";
    }

    public function appendLogo()
    {
        return $this->makeVisible('logo')->setAppends(['logo']);
    }

    public function getLogoAttribute()
    {
        return ThumbnailManager::retrieveLogoThumbnails(
            $this->image,
            $this->thumbnailProperties()
        );
    }

    public function getLogoDimensionsAttribute()
    {
        return ThumbnailManager::retrieveLogoDimensions(
            $this->image,
            $this->thumbnailProperties(),
            static::class
        );
    }

    public function getLogoSelectionAttribute()
    {
        return ThumbnailManager::retrieveLogoDimensions(
            $this->image,
            $this->thumbnailProperties(),
            static::class,
            true,
            true
        );
    }

    public function getLogoSelectionWithKeysAttribute()
    {
        return ThumbnailManager::retrieveLogoDimensions(
            $this->image,
            $this->thumbnailProperties(),
            static::class,
            true
        );
    }
}
