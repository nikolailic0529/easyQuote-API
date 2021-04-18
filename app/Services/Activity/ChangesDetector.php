<?php

namespace App\Services\Activity;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ChangesDetector
{
    const OLD_ATTRS_KEY = 'old';

    const NEW_ATTRS_KEY = 'attributes';

    /**
     * Get attribute values of the model to be logged.
     *
     * @param Model $model
     * @param string[] $logAttributes
     * @param array $oldAttributeValues
     * @return array
     */
    public function getAttributeValuesToBeLogged(Model $model, array $logAttributes, array $oldAttributeValues = []): array
    {
        if (empty($logAttributes)) {
            return [];
        }

        $model->refresh();

        $properties = [
            self::NEW_ATTRS_KEY => $this->getModelChanges($model, $logAttributes),
        ];

        if (!empty($oldAttributeValues)) {
            $nullProperties = array_fill_keys($logAttributes, null);

            $properties[self::OLD_ATTRS_KEY] = array_merge($nullProperties, $oldAttributeValues);
        }

        if (isset($properties[self::OLD_ATTRS_KEY])) {
            $properties[self::NEW_ATTRS_KEY] = array_udiff_assoc(
                $properties[self::NEW_ATTRS_KEY],
                $properties[self::OLD_ATTRS_KEY],
                function ($new, $old) {
                    if ($old === null || $new === null) {
                        return $new === $old ? 0 : 1;
                    }

                    return $new <=> $old;
                }
            );

            $properties[self::OLD_ATTRS_KEY] = Arr::only($properties[self::OLD_ATTRS_KEY], $logAttributes);
        }

        return $properties;
    }

    public function diffAttributeValues(array $oldAttributeValues, array $newAttributeValues, array $logAttributes = null): array
    {
        $logAttributes ??= array_keys($newAttributeValues);

        $properties = [
            self::NEW_ATTRS_KEY => $newAttributeValues,
            self::OLD_ATTRS_KEY => array_merge(array_fill_keys($logAttributes, null), $oldAttributeValues)
        ];

        $properties[self::NEW_ATTRS_KEY] = array_udiff_assoc(
            $properties[self::NEW_ATTRS_KEY],
            $properties[self::OLD_ATTRS_KEY],
            function ($new, $old) {
                if ($old === null || $new === null) {
                    return $new === $old ? 0 : 1;
                }

                return $new <=> $old;
            }
        );

        $properties[self::OLD_ATTRS_KEY] = Arr::only($properties[self::OLD_ATTRS_KEY], array_keys($properties[self::NEW_ATTRS_KEY]));

        return $properties;
    }

    /**
     * Get changes of the model.
     *
     * @param Model $model
     * @param string[] $attributes
     * @return array
     */
    public function getModelChanges(Model $model, array $attributes): array
    {
        $changes = [];

        foreach ($attributes as $attribute) {
            if (Str::contains($attribute, '.')) {
                $changes += $this->getRelatedModelAttributeValue($model, $attribute);

                continue;
            }

            if (Str::contains($attribute, '->')) {
                Arr::set(
                    $changes,
                    str_replace('->', '.', $attribute),
                    static::getModelAttributeJsonValue($model, $attribute)
                );

                continue;
            }

            $changes[$attribute] = $model->getAttribute($attribute);

            if (is_null($changes[$attribute])) {
                continue;
            }

            if (in_array($attribute, $model->getDates(), true) ||
                $model->hasCast($attribute, ['date', 'datetime'])) {
                $changes[$attribute] = $model->serializeDate(
                    $model->asDateTime($changes[$attribute])
                );
            }

            if ($model->hasCast($attribute)) {
                $cast = $model->getCasts()[$attribute];

                if ($model->isCustomDateTimeCast($cast)) {
                    $changes[$attribute] = $model->asDateTime($changes[$attribute])->format(explode(':', $cast, 2)[1]);
                }
            }
        }

        return $changes;
    }

    protected static function getRelatedModelAttributeValue(Model $model, string $attribute): array
    {
        $relatedModelNames = explode('.', $attribute);
        $relatedAttribute = array_pop($relatedModelNames);

        $attributeName = [];
        $relatedModel = $model;

        do {
            $attributeName[] = $relatedModelName = static::getRelatedModelRelationName($relatedModel, array_shift($relatedModelNames));

            $relatedModel = $relatedModel->$relatedModelName ?? $relatedModel->$relatedModelName();
        } while (!empty($relatedModelNames));

        $attributeName[] = $relatedAttribute;

        return [implode('.', $attributeName) => $relatedModel->$relatedAttribute ?? null];
    }

    protected static function getRelatedModelRelationName(Model $model, string $relation): string
    {
        return Arr::first([
            $relation,
            Str::snake($relation),
            Str::camel($relation),
        ], function (string $method) use ($model): bool {
            return method_exists($model, $method);
        }, $relation);
    }

    /**
     * @param Model $model
     * @param string $attribute
     * @return mixed
     */
    protected static function getModelAttributeJsonValue(Model $model, string $attribute)
    {
        $path = explode('->', $attribute);
        $modelAttribute = array_shift($path);
        $modelAttribute = collect($model->getAttribute($modelAttribute));

        return data_get($modelAttribute, implode('.', $path));
    }
}
