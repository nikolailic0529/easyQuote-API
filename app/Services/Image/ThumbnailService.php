<?php

namespace App\Services\Image;

use App\Contracts\HasImagesDirectory;
use App\Contracts\WithLogo;
use App\Models\Image;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Intervention\Image\Constraint;
use Intervention\Image\ImageManagerStatic as ImageIntervention;

class ThumbnailService
{
    const DEFAULT_EXT = 'png';

    public function __construct(protected Filesystem $filesystem)
    {
    }

    public function createThumbnailsFor(\SplFileInfo $file, Model&WithLogo $model): Image
    {
        return tap(new Image(), function (Image $image) use ($file, $model) {
            $modelImagesDir = (string)Str::of($model::class)
                ->classBasename()
                ->plural()
                ->snake()
                ->prepend('images', DIRECTORY_SEPARATOR, $model->getKey(), DIRECTORY_SEPARATOR);

            if ($model instanceof HasImagesDirectory) {
                $modelImagesDir = $model->imagesDirectory();
            }

            $this->filesystem->makeDirectory($modelImagesDir);

            $thumbnails = $this->generateThumbnailsUsing($file, $modelImagesDir, $model->thumbnailProperties());

            $stream = fopen($file->getRealPath(), 'r');

            $filepath = 'images/'.Str::random(40).'.'.static::resolveFileExtension($file);

            $this->filesystem->writeStream($filepath, $stream);

            $image->imageable()->associate($model);
            $image->original = $filepath;
            $image->thumbnails = $thumbnails;

            $image->save();
        });
    }

    protected static function resolveFileExtension(\SplFileInfo $file): string
    {
        return $file->getExtension() ?: static::DEFAULT_EXT;
    }

    public function generateThumbnailsUsing(\SplFileInfo $file, string $directory, array $properties): array
    {
        return collect($properties)
            ->mapWithKeys(function (array $size, string|int $key) use ($file, $directory) {
                if (!isset($size['width']) || !isset($size['height'])) {
                    throw new \InvalidArgumentException("Thumbnail properties must have width & height set.");
                }

                $image = ImageIntervention::make($file);

                $image->resize((int)$size['width'], (int)$size['height'], static function (Constraint $constraint): void {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                $basename = sprintf("%s@%s.%s", Str::random(40), (string)$key, static::resolveFileExtension($file));

                $relativePath = implode(DIRECTORY_SEPARATOR, [$directory, $basename]);

                $filepath = $this->filesystem->path($relativePath);

                $image->save($filepath, quality: 100);

                return [$key => $relativePath];
            })
            ->all();
    }
}