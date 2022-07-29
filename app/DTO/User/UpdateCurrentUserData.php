<?php

namespace App\DTO\User;

use App\DTO\MissingValue;
use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;

final class UpdateCurrentUserData extends DataTransferObject
{
    /** @var string|\App\DTO\MissingValue */
    public string|MissingValue $first_name;
    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $middle_name;
    /** @var string|\App\DTO\MissingValue */
    public string|MissingValue $last_name;
    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $phone;
    /** @var string|\App\DTO\MissingValue */
    public string|MissingValue $timezone_id;
    /** @var string|\App\DTO\MissingValue */
    public string|MissingValue $country_id;
    /** @var \App\DTO\SalesUnit\CreateSalesUnitRelationData[]|\App\DTO\MissingValue */
    public array|MissingValue $sales_units;
    /** @var \Illuminate\Http\UploadedFile|\App\DTO\MissingValue|null */
    public UploadedFile|MissingValue|null $picture;
    /** @var bool|\App\DTO\MissingValue */
    public bool|MissingValue $delete_picture = false;
    /** @var bool|\App\DTO\MissingValue */
    public bool|MissingValue $change_password = false;
    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $password;
    /** @var string|\App\DTO\MissingValue|null */
    public string|MissingValue|null $default_route;
    /** @var int|\App\DTO\MissingValue */
    public int|MissingValue $recent_notifications_limit;
}