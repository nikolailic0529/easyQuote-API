<?php

namespace App\DTO\User;

use App\Models\Data\Country;
use App\Models\Data\Timezone;
use App\Models\Template\HpeContractTemplate;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Optional;
use Illuminate\Validation\Rule as BaseRule;
use Spatie\LaravelData\Attributes\Validation\Image;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Symfony\Component\Validator\Constraints\Uuid;

final class UpdateCurrentUserData extends Data
{
    public function __construct(
        #[Min(2), Rule('alpha_spaces')]
        public string|Optional $first_name,
        #[Rule('alpha_spaces'), Nullable]
        public string|Optional|null $middle_name,
        #[Min(2), Rule('alpha_spaces')]
        public string|Optional $last_name,
        #[Min(4), Rule('phone'), Nullable]
        public string|Optional|null $phone,
        #[Uuid]
        public string|Optional $timezone_id,
        #[Uuid]
        public string|Optional $country_id,
        #[Uuid]
        public string|Optional|null $hpe_contract_template_id,
        #[Image, Max(2048)]
        public UploadedFile|Optional $picture,
        public bool|Optional $delete_picture,
        public bool|Optional $change_password,
        #[RequiredIf('change_password', true)]
        public string|Optional|null $password,
        #[RequiredIf('change_password', true)]
        public string|Optional|null $current_password,
        public string|Optional|null $default_route,
        #[Min(1), Max(30)]
        public int|Optional $recent_notifications_limit
    ) {
    }

    public static function rules(...$args): array
    {
        return [
            'timezone_id' => [
                BaseRule::exists(Timezone::class, 'id'),
            ],
            'country_id' => [
                BaseRule::exists(Country::class, 'id')->withoutTrashed(),
            ],
            'hpe_contract_template_id' => [
                'nullable',
                BaseRule::exists(HpeContractTemplate::class, 'id')->withoutTrashed(),
            ],
        ];
    }
}