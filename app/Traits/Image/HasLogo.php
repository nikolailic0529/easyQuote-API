<?php

namespace App\Traits\Image;

use Str, File;

trait HasLogo
{
    public function getLogoAttribute()
    {
        if (!isset($this->image)) {
            return null;
        }

        return $this->image->thumbnails;
    }

    public function thumbnailProperties(): array
    {
        return [
            [
                'width' => 60,
                'height' => 30
            ],
            [
                'width' => 120,
                'height' => 60
            ],
            [
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

    public function getLogoDimensionsAttribute(?bool $withKeys = false, ?bool $absPath = false)
    {
        if (!is_array($this->logo)) {
            return $this->logo;
        }

        $name = Str::snake(class_basename($this));
        $method = $withKeys ? 'mapWithKeys' : 'transform';

        return collect($this->logo)->values()->{$method}(function ($src, $key) use ($name, $withKeys, $absPath) {
            $id = $name . '_logo_x' . ($key + 1);
            $width = $this->thumbnailProperties()[$key]['width'];
            $height = $this->thumbnailProperties()[$key]['height'];
            $label = "Logo {$width}X{$height}";
            $is_image = true;
            $src = $absPath ? File::abspath($src) : $src;

            $entity = compact('id', 'label', 'src', 'is_image');

            return $withKeys ? [$id => $src] : $entity;
        })->toArray();
    }
}
