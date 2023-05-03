<?php

namespace App\Domain\Activity\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\ArrayShape;

class ChangesDetector
{
    const OLD_ATTRS_KEY = 'old';

    const NEW_ATTRS_KEY = 'attributes';

    /**
     * Get attribute values of the model to be logged.
     *
     * @param string[] $logAttributes
     */
    public function getAttributeValuesToBeLogged(
        Model $model,
        array $logAttributes,
        array $oldAttributeValues = [],
        bool $diff = false,
    ): array {
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

        if ($diff) {
            return $this->diffAttributeValues(
                oldAttributeValues: $properties[self::OLD_ATTRS_KEY] ?? [],
                newAttributeValues: $properties[self::NEW_ATTRS_KEY] ?? [],
                logAttributes: $logAttributes,
            );
        }

        return $properties;
    }

    #[ArrayShape([self::NEW_ATTRS_KEY => 'array', self::OLD_ATTRS_KEY => 'array'])]
    public function diffAttributeValues(array $oldAttributeValues, array $newAttributeValues, array $logAttributes = null): array
    {
        $logAttributes ??= array_keys($newAttributeValues);

        $properties = [
            self::NEW_ATTRS_KEY => $newAttributeValues,
            self::OLD_ATTRS_KEY => array_merge(array_fill_keys($logAttributes, null), $oldAttributeValues),
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
     * @param string[] $attributes
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
                $changes[$attribute] = $this->serializeDate(
                    $this->asDateTime($model, $changes[$attribute])
                );
            }

            if ($model->hasCast($attribute)) {
                $cast = $model->getCasts()[$attribute];

                if ($this->isCustomDateTimeCast($cast)) {
                    $changes[$attribute] = $this->asDateTime($model, $changes[$attribute])->format(explode(':', $cast, 2)[1]);
                }
            }
        }

        return $changes;
    }

    public function isLogEmpty(array|\ArrayAccess $attrs): bool
    {
        return empty($attrs[self::NEW_ATTRS_KEY] ?? []) && empty($attrs[self::OLD_ATTRS_KEY] ?? []);
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
     * @return mixed
     */
    protected static function getModelAttributeJsonValue(Model $model, string $attribute)
    {
        $path = explode('->', $attribute);
        $modelAttribute = array_shift($path);
        $modelAttribute = collect($model->getAttribute($modelAttribute));

        return data_get($modelAttribute, implode('.', $path));
    }

    protected function asDateTime(Model $model, $value): \DateTimeInterface
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof CarbonInterface) {
            return Date::instance($value);
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof \DateTimeInterface) {
            return Date::parse(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return Date::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        if ($this->isStandardDateFormat($value)) {
            return Date::instance(Carbon::createFromFormat('Y-m-d', $value)->startOfDay());
        }

        $format = $model->getDateFormat();

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        try {
            $date = Date::createFromFormat($format, $value);
        } catch (\InvalidArgumentException $e) {
            $date = false;
        }

        return $date ?: Date::parse($value);
    }

    protected function isStandardDateFormat(mixed $value): bool
    {
        return preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value);
    }

    /**
     * Determine if the cast type is a custom date time cast.
     */
    protected function isCustomDateTimeCast(string $cast): bool
    {
        return strncmp($cast, 'date:', 5) === 0 ||
            strncmp($cast, 'datetime:', 9) === 0;
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date instanceof \DateTimeImmutable ?
            CarbonImmutable::instance($date)->toJSON() :
            Carbon::instance($date)->toJSON();
    }
}
