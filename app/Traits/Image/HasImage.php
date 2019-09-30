<?php namespace App\Traits\Image;

use App\Contracts\WithImage;
use App\Models\Image;
use Illuminate\Http\UploadedFile;
use ImageIntervention, Storage;

trait HasImage
{
    public function image()
    {
        return $this->morphOne(Image::class, 'imageable');
    }

    public function createImage(UploadedFile $file)
    {
        if(!$file instanceof UploadedFile || !$this instanceof WithImage) {
            return $this;
        }

        $modelImagesDir = $this->imagesDirectory();

        $original = $file->store($modelImagesDir, 'public');

        $image = ImageIntervention::make(Storage::path("public/{$original}"));
        $image->resize($this->thumbnailProperties()['width'], $this->thumbnailProperties()['height'], function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        });

        $thumbnail = "{$modelImagesDir}/{$image->filename}@thumb.{$image->extension}";
        $image->save(Storage::path("public/{$thumbnail}"));

        $this->image()->delete();
        $this->image()->create(compact('original', 'thumbnail'));

        return $this->load('image');
    }
}
