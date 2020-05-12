<?php

namespace App\Traits\Image;

use App\Contracts\WithImage;
use App\Models\Image;
use Illuminate\Http\UploadedFile;
use ImageIntervention, Storage;

trait HasImage
{
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable')->cacheForever();
    }

    public function createImage($file, array $properties = [])
    {
        if (!$file instanceof UploadedFile || !$this instanceof WithImage) {
            return $this;
        }

        $modelImagesDir = $this->imagesDirectory();

        $image = ImageIntervention::make($file->get());

        if (filled($properties) && isset($properties['width']) && isset($properties['height'])) {
            $image->resize($properties['width'], $properties['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
        }

        $storageDir = "public/{$modelImagesDir}";
        !Storage::exists($storageDir) && Storage::makeDirectory($storageDir);

        $original = "{$modelImagesDir}/{$file->hashName()}";
        $image->save(Storage::path("public/{$original}"));

        $this->image()->delete();

        $this->image()->create(compact('original'));

        $this->image()->flushQueryCache();

        return $this->load('image');
    }

    public function deleteImage()
    {
        $this->image()->flushQueryCache()->delete();
    }

    public function deleteImageWhen($value)
    {
        if (!$value) {
            return;
        }

        $this->image()->flushQueryCache()->delete();
    }
}
