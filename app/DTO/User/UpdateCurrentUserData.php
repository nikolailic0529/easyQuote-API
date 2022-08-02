<?php

namespace App\DTO\User;

use App\DTO\Enum\DataTransferValueOption;
use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;

final class UpdateCurrentUserData extends DataTransferObject
{
    /** @var string|\App\DTO\Enum\DataTransferValueOption */
    public string|DataTransferValueOption $first_name = DataTransferValueOption::Miss;
    /** @var string|\App\DTO\Enum\DataTransferValueOption|null */
    public string|DataTransferValueOption|null $middle_name = DataTransferValueOption::Miss;
    /** @var string|\App\DTO\Enum\DataTransferValueOption */
    public string|DataTransferValueOption $last_name = DataTransferValueOption::Miss;
    /** @var string|\App\DTO\Enum\DataTransferValueOption|null */
    public string|DataTransferValueOption|null $phone = DataTransferValueOption::Miss;
    /** @var string|\App\DTO\Enum\DataTransferValueOption */
    public string|DataTransferValueOption $timezone_id = DataTransferValueOption::Miss;
    /** @var string|\App\DTO\Enum\DataTransferValueOption */
    public string|DataTransferValueOption $country_id = DataTransferValueOption::Miss;
    /** @var \App\DTO\SalesUnit\CreateSalesUnitRelationData[]|\App\DTO\Enum\DataTransferValueOption */
    public array|DataTransferValueOption $sales_units = DataTransferValueOption::Miss;
    /** @var \Illuminate\Http\UploadedFile|\App\DTO\Enum\DataTransferValueOption|null */
    public UploadedFile|DataTransferValueOption|null $picture = DataTransferValueOption::Miss;
    /** @var bool|\App\DTO\Enum\DataTransferValueOption */
    public bool|DataTransferValueOption $delete_picture = DataTransferValueOption::Miss;
    /** @var bool|\App\DTO\Enum\DataTransferValueOption */
    public bool|DataTransferValueOption $change_password = DataTransferValueOption::Miss;
    /** @var string|\App\DTO\Enum\DataTransferValueOption|null */
    public string|DataTransferValueOption|null $password = DataTransferValueOption::Miss;
    /** @var string|\App\DTO\Enum\DataTransferValueOption|null */
    public string|DataTransferValueOption|null $default_route = DataTransferValueOption::Miss;
    /** @var int|\App\DTO\Enum\DataTransferValueOption */
    public int|DataTransferValueOption $recent_notifications_limit = DataTransferValueOption::Miss;
}