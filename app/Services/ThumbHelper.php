<?php

namespace App\Services;

use App\Contracts\{HasImagesDirectory, WithLogo};
use App\Models\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\{File as IlluminateFile, UploadedFile};
use Illuminate\Support\{Arr, Collection as BaseCollection, Facades\Storage, Str};
use Intervention\Image\Constraint;
use Intervention\Image\ImageManagerStatic as ImageIntervention;
use Symfony\Component\HttpFoundation\File\File;

class ThumbHelper
{
    const WITH_KEYS = 1;

    const ABS_PATH = 2;

    const PREFER_SVG = 4;

    public static function createLogoThumbnails(Model $model, $file, $fake = false): Model
    {
        if (!$fake && (!$file instanceof UploadedFile || !$model instanceof WithLogo || !$model instanceof HasImagesDirectory)) {
            return $model;
        }

        $modelImagesDir = $model->imagesDirectory();

        $originalFileName = with($file, function () use ($model, $modelImagesDir, $file, $fake): string {

            if ($fake) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $fileName = sprintf('%s/%s.%s', $modelImagesDir, $model->getKey(), $extension);

                Storage::put("public/$fileName", file_get_contents($file), 'public');

                return $fileName;
            }

            $fileName = sprintf('%s.%s', $model->getKey(), $file->getClientOriginalExtension());

            $file->storeAs($modelImagesDir, $fileName, ['disk' => 'public']);

            return sprintf('%s/%s', $modelImagesDir, $fileName);

        });

        $thumbnails = collect($model->thumbnailProperties())->mapWithKeys(function ($size, $key) use ($model, $originalFileName, $modelImagesDir) {
            if (!isset($size['width']) || !isset($size['height'])) {
                return true;
            }

            $image = ImageIntervention::make(Storage::path("public/$originalFileName"));
            $image
//                ->trim()
                ->resize($size['width'], $size['height'], function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });

            $thumbFilePath = "$modelImagesDir/{$model->getKey()}@$key.$image->extension";
            $image->save(Storage::path("public/$thumbFilePath"), 100);

            return [$key => $thumbFilePath];
        });

        if (blank($thumbnails)) {
            return $model;
        }

        $model->image()->delete();
        $model->image()->flushQueryCache();
        $model->image()->create(compact('originalFileName', 'thumbnails'));

        return $model->load('image');
    }

    public static function getImageThumbnails(?Image $image, array $properties): array
    {
        if (is_null($image) || !is_array($image->thumbnails)) {
            return [];
        }

        if (!Arr::isAssoc($properties)) {
            throw new \InvalidArgumentException("The properties must be an associative array.");
        }

        return Arr::only($image->thumbnails, array_keys($properties));
    }

    public static function getLogoDimensionsFromImage(
        ?Image $image,
        array $thumbnailProperties,
        string $keyPrefix = '',
        int $flags = 0
    ): array
    {

        if (is_null($image) || is_null($image->thumbnails)) {
            return [];
        }

        $withKeys = (bool)($flags & ThumbHelper::WITH_KEYS);
        $absPath = (bool)($flags & ThumbHelper::ABS_PATH);
        $preferSvg = (bool)($flags & ThumbHelper::PREFER_SVG);

        $filtered = with(BaseCollection::wrap($image->thumbnails), function (BaseCollection $thumbnails) use ($thumbnailProperties, $preferSvg) {

            if ($preferSvg) {
                $svgThumbnails = ThumbHelper::filterCollectionKeysStartingWith($thumbnails, 'svg');

                if ($svgThumbnails->isNotEmpty()) {
                    return $svgThumbnails;
                }
            }

            return $thumbnails->only(array_keys($thumbnailProperties));
        });

        if (!empty($keyPrefix)) {
            $keyPrefix = rtrim($keyPrefix, '_').'_logo_';
        }

        if ($withKeys) {
            return $filtered->mapWithKeys(function ($src, $key) use ($keyPrefix, $absPath, $thumbnailProperties) {
                $id = $keyPrefix.str_replace('svg_', '', $key);

                $src = $absPath ? ThumbHelper::parseAbsPath($src) : $src;

                return [$id => $src];
            })
                ->all();
        }

        return $filtered->map(function ($src, $key) use ($keyPrefix, $absPath, $thumbnailProperties) {
            $id = $keyPrefix.str_replace('svg_', '', $key);

            $src = $absPath ? ThumbHelper::parseAbsPath($src) : $src;

            $width = Arr::get($thumbnailProperties, "{$key}.width");
            $height = Arr::get($thumbnailProperties, "{$key}.height");

            return [
                'id' => $id,
                'label' => "Logo {$width}X{$height}",
                'src' => $src,
                'is_image' => true,
            ];
        })
            ->values()
            ->all();
    }

    public static function retrieveLogoFromModels(array $models, int $flags = 0): array
    {
        foreach ($models as $model) {
            if (!$model instanceof Model) {
                throw new \InvalidArgumentException("Model must be instance of ".Model::class);
            }
        }

        $logo = [];

        foreach ($models as $model) {
            array_merge($logo, ThumbHelper::getLogoDimensionsFromImage(
                $model->image,
                $model->thumbnailProperties(),
                Str::snake(class_basename($model)),
                $flags
            ));
        }

        return $logo;
    }

    public static function updateModelSvgThumbnails(WithLogo $model, string $filepath): void
    {
        $thumbs = BaseCollection::wrap($model->thumbnailProperties())
            ->mapWithKeys(function ($properties, $key) use ($filepath) {
                $base64 = ThumbHelper::svgUrlEncode($filepath, $properties['width'], $properties['height']);

                $key = (string)Str::of($key)->prepend('svg_');

                return [$key => $base64];
            });

        $model->image->thumbnails = array_merge($model->image->thumbnails, $thumbs->toArray());

        $model->image->save();
    }

    public static function svgUrlEncode(string $filepath, int $width, int $height)
    {
        $data = file_get_contents($filepath);

        return (string)Str::of($data)
            ->replace('{width}', $width)
            ->replace('{height}', $height)
            ->replaceMatches('/\v(?:[\v\h]+)/', ' ')
            ->replace('"', "'")
            ->when(true, fn($string) => Str::of(rawurlencode((string)$string)))
            ->replace('%20', ' ')
            ->replace('%3D', '=')
            ->replace('%3A', ':')
            ->replace('%2F', '/')
            ->prepend('data:image/svg+xml,');
    }

    protected static function filterCollectionKeysStartingWith(BaseCollection $collection, string $startingWith): BaseCollection
    {
        return $collection->filter(fn($value, $key) => Str::startsWith($key, $startingWith));
    }

    protected static function parseAbsPath(string $src)
    {
        if (str_contains($src, 'svg+xml')) {
            return $src;
        }

        return storage_path('app/public/'.Str::after($src, 'storage/'));
    }
}
