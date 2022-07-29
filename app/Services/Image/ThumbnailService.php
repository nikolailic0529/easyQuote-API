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

    public function createResizedImageFor(\SplFileInfo $file, Model $model, array $properties): Image
    {
        return tap(new Image(), function (Image $image) use ($file, $model, $properties) {
            $modelImagesDir = static::resolveImagesDirectoryFor($model);

            $this->filesystem->makeDirectory($modelImagesDir);

            $imageInter = ImageIntervention::make($file);

            $imageInter->resize((int)$properties['width'], (int)$properties['height'], static function (Constraint $constraint): void {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $filepath = Str::random(40).'.'.static::resolveFileExtension($file);
            $relativePath = implode(DIRECTORY_SEPARATOR, [$modelImagesDir, $filepath]);

            $this->filesystem->put($relativePath, $imageInter->encode(static::resolveFileExtension($file), quality: 100));

            $image->imageable()->associate($model);
            $image->original = $relativePath;

            $image->save();
        });
    }

    public function createThumbnailsFor(\SplFileInfo $file, Model&WithLogo $model): Image
    {
        return tap(new Image(), function (Image $image) use ($file, $model) {
            $modelImagesDir = static::resolveImagesDirectoryFor($model);

            $this->filesystem->makeDirectory($modelImagesDir);

            $thumbnails = $this->generateThumbnailsUsing($file, $modelImagesDir, $model->thumbnailProperties());

            $stream = fopen($file->getRealPath(), 'r');

            $filepath = Str::random(40).'.'.static::resolveFileExtension($file);
            $relativePath = implode(DIRECTORY_SEPARATOR, [$modelImagesDir, $filepath]);

            $this->filesystem->writeStream($relativePath, $stream);

            $image->imageable()->associate($model);
            $image->original = $relativePath;
            $image->thumbnails = $thumbnails;

            $image->save();
        });
    }

    protected static function resolveImagesDirectoryFor(Model $model): string
    {
        if ($model instanceof HasImagesDirectory) {
            return $model->imagesDirectory();
        }

        return (string)Str::of($model::class)
            ->classBasename()
            ->plural()
            ->snake()
            ->prepend('images', DIRECTORY_SEPARATOR, $model->getKey(), DIRECTORY_SEPARATOR);
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