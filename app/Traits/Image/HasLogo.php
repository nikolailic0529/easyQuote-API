<?php

namespace App\Traits\Image;

use App\Contracts\{
    WithImage,
    WithLogo
};
use Illuminate\Http\{
    File as IlluminateFile,
    UploadedFile
};
use ImageIntervention, Storage, Str, File;

trait HasLogo
{
    public function createLogo($file, $fake = false)
    {
        if (!$fake && (!$file instanceof UploadedFile || !$this instanceof WithLogo || !$this instanceof WithImage)) {
            return $this;
        }

        $modelImagesDir = $this->imagesDirectory();

        $original = $fake
            ? Str::after(Storage::putFile("public/{$modelImagesDir}", new IlluminateFile(base_path($file)), 'public'), 'public/')
            : $file->store($modelImagesDir, 'public');

        $thumbnails = collect($this->thumbnailProperties())->mapWithKeys(function ($size, $key) use ($original, $modelImagesDir) {
            if (!isset($size['width']) || !isset($size['height'])) {
                return true;
            }

            $image = ImageIntervention::make(Storage::path("public/{$original}"));
            $image->resize($size['width'], $size['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->resizeCanvas($size['width'], $size['height'], 'center');

            $key++;
            $imageKey = "x{$key}";

            $thumbnail = "{$modelImagesDir}/{$image->filename}@{$imageKey}.{$image->extension}";
            $image->save(Storage::path("public/{$thumbnail}"), 100);

            return [$imageKey => $thumbnail];
        });

        if(blank($thumbnails)) {
            return $this;
        }

        $this->image()->delete();
        $this->image()->create(compact('original', 'thumbnails'));

        return $this->load('image');
    }

    public function getLogoAttribute()
    {
        return $this->image->thumbnails ?? null;
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

    public function getLogoSelectionAttribute()
    {
        return $this->getLogoDimensionsAttribute(true, true);
    }

    public function getLogoSelectionWithKeysAttribute()
    {
        return $this->getLogoDimensionsAttribute(true);
    }
}
