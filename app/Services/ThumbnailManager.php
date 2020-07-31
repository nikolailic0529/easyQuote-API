<?php

namespace App\Services;

use App\Contracts\{
    WithImage,
    WithLogo
};
use Illuminate\Http\{
    File as IlluminateFile,
    UploadedFile
};
use Intervention\Image\ImageManagerStatic as ImageIntervention;
use App\Models\Image;
use Illuminate\Support\{Arr, Collection, Str, Facades\Storage};
use File;
use Illuminate\Database\Eloquent\Model;

class ThumbnailManager
{
    public static function createLogoThumbnails(Model $model, $file, $fake = false): Model
    {
        if (!$fake && (!$file instanceof UploadedFile || !$model instanceof WithLogo || !$model instanceof WithImage)) {
            return $model;
        }

        $modelImagesDir = $model->imagesDirectory();

        $original = $fake
            ? Str::after(Storage::putFile("public/{$modelImagesDir}", new IlluminateFile(base_path($file)), 'public'), 'public/')
            : $file->store($modelImagesDir, 'public');

        $thumbnails = collect($model->thumbnailProperties())->mapWithKeys(function ($size, $key) use ($original, $modelImagesDir) {
            if (!isset($size['width']) || !isset($size['height'])) {
                return true;
            }

            $image = ImageIntervention::make(Storage::path("public/{$original}"));
            $image->resize($size['width'], $size['height'], function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
                ->resizeCanvas($size['width'], $size['height'], 'center');

            $thumbnail = "{$modelImagesDir}/{$image->filename}@{$key}.{$image->extension}";
            $image->save(Storage::path("public/{$thumbnail}"), 100);

            return [$key => $thumbnail];
        });

        if (blank($thumbnails)) {
            return $model;
        }

        $model->image()->delete();
        $model->image()->flushQueryCache();
        $model->image()->create(compact('original', 'thumbnails'));

        return $model->load('image');
    }

    public static function retrieveLogoThumbnails(?Image $image, array $properties): ?array
    {
        if (is_null($image) || is_null($image->thumbnails)) {
            return null;
        }

        return Collection::wrap($image->thumbnails)
            ->only(array_keys($properties))
            ->whenEmpty(fn () => null, fn ($collection) => $collection->toArray());
    }

    public static function retrieveLogoDimensions(
        ?Image $image,
        array $properties,
        string $classname,
        bool $withKeys = false,
        bool $absPath = false,
        bool $preferSvg = false
    ): array {
        if (is_null($image) || is_null($image->thumbnails)) {
            return [];
        }

        $name = Str::snake(class_basename($classname));
        $method = $withKeys ? 'mapWithKeys' : 'transform';

        $thumbnails = Collection::wrap($image->thumbnails);

        $filtered = $thumbnails
            ->when(
                true === $preferSvg,
                fn (Collection $thumbs) => static::filterCollectionKeysStartingWith($thumbs, 'svg'),
                fn (Collection $thumbs) => $thumbs->only(array_keys($properties))
            )
            ->whenEmpty(fn () => $thumbnails->only(array_keys($properties)));

        return $filtered
            ->{$method}(function ($src, $key) use ($name, $withKeys, $absPath, $properties) {
                $id = (string) Str::of($key)->replace('svg_', '')->prepend($name, '_logo_');

                $width = Arr::get($properties, "{$key}.width");
                $height = Arr::get($properties, "{$key}.height");

                $label = "Logo {$width}X{$height}";
                $is_image = true;

                $abs_src = static::parseAbsPath($src);

                $src = $absPath ? static::parseAbsPath($src) : $src;

                $entity = compact('id', 'label', 'src', 'abs_src', 'is_image');

                return $withKeys ? [$id => $src] : $entity;
            })
            ->when(false === $withKeys, fn (Collection $thumbs) => $thumbs->values())
            ->toArray();
    }

    public static function retrieveLogoFromModels(bool $withKeys, bool $absPath, bool $preferSvg, WithLogo ...$models): array
    {
        return Collection::wrap($models)->map(
            fn (WithLogo $model) =>
            static::retrieveLogoDimensions(
                $model->image,
                $model->thumbnailProperties(),
                get_class($model),
                $withKeys,
                $absPath,
                $preferSvg
            )
        )->collapse()->toArray();
    }

    public static function updateModelSvgThumbnails(WithLogo $model, string $filepath): void
    {
        $thumbs = Collection::wrap($model->thumbnailProperties())
            ->mapWithKeys(function ($properties, $key) use ($filepath) {
                $base64 = static::svgUrlEncode($filepath, $properties['width'], $properties['height']);

                $key = (string) Str::of($key)->prepend('svg_');

                return [$key => $base64];
            });

        $model->image->thumbnails = array_merge($model->image->thumbnails, $thumbs->toArray());

        $model->image->save();
    }

    public static function svgUrlEncode(string $filepath, int $width, int $height)
    {
        $data = file_get_contents($filepath);

        return (string) Str::of($data)
            ->replace('{width}', $width)
            ->replace('{height}', $height)
            ->replaceMatches('/\v(?:[\v\h]+)/', ' ')
            ->replace('"', "'")
            ->when(true, fn ($string) => Str::of(rawurlencode((string) $string)))
            ->replace('%20', ' ')
            ->replace('%3D', '=')
            ->replace('%3A', ':')
            ->replace('%2F', '/')
            ->prepend('data:image/svg+xml,');
    }

    protected static function filterCollectionKeysStartingWith(Collection $collection, string $startingWith): Collection
    {
        return $collection->filter(fn ($value, $key) => Str::startsWith($key, $startingWith));
    }

    protected static function parseAbsPath($src)
    {
        return (string) Str::of($src)->when(Str::contains($src, 'svg+xml'), fn () => $src, fn () => File::abspath($src));
    }
}
