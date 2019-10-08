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
}
