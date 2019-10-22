<?php namespace App\Traits\Image;

use Str, ImageIntervention;

trait HasLogo
{
    public function getLogoAttribute()
    {
        if(!isset($this->image)) {
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

    public function getLogoDimensionsAttribute()
    {
        if(!is_array($this->logo)) {
            return $this->logo;
        }

        $name = Str::snake(class_basename($this));

        return collect($this->logo)->values()->transform(function ($src, $key) use ($name) {
            $id = $name . '_logo_x' . ($key + 1);
            $width = $this->thumbnailProperties()[$key]['width'];
            $height = $this->thumbnailProperties()[$key]['height'];
            $label = "Logo {$width}X{$height}";
            $is_image = true;

            return compact('id', 'label', 'src', 'is_image');
        })->toArray();
    }
}
