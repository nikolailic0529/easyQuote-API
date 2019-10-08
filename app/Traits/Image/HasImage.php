<?php namespace App\Traits\Image;

use App\Contracts\WithImage;
use App\Models\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\File;
use ImageIntervention, Storage, Str;

trait HasImage
{
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function createImage($file, $fake = false)
    {
        if((!$file instanceof UploadedFile || !$this instanceof WithImage) && !$fake) {
            return $this;
        }

        $modelImagesDir = $this->imagesDirectory();

        if($fake) {
            $original = Storage::putFile("public/$modelImagesDir", new File(base_path($file)), 'public');
            $original = Str::after($original, 'public/');
        } else {
            $original = $file->store($modelImagesDir, 'public');
        }

        $thumbnails = collect($this->thumbnailProperties())->mapWithKeys(function ($size, $key) use ($original, $modelImagesDir) {
            if(!isset($size['width']) || !isset($size['height'])) {
                return true;
            }

            $image = ImageIntervention::make(Storage::path("public/{$original}"));
            $image->resize($size['width'], $size['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $key++;
            $imageKey = "x{$key}";

            $thumbnail = "{$modelImagesDir}/{$image->filename}@{$imageKey}.{$image->extension}";
            $image->save(Storage::path("public/{$thumbnail}"));

            return [$imageKey => $thumbnail];
        });

        $this->image()->delete();
        $this->image()->create(compact('original', 'thumbnails'));

        return $this->load('image');
    }
}
