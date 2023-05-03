<?php

namespace App\Domain\User\DataTransferObjects;

use App\Domain\Country\Models\Country;
use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Notification\DataTransferObjects\UpdateNotificationSettingsGroupData;
use App\Domain\Timezone\Models\Timezone;
use App\Domain\User\Enum\UserLanguageEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule as BaseRule;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\CurrentPassword;
use Spatie\LaravelData\Attributes\Validation\Different;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Image;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Nullable;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
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
        #[StringType, Enum(UserLanguageEnum::class)]
        public UserLanguageEnum|Optional $language,
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
        #[
            RequiredIf('change_password', true),
            Different('current_password')
        ]
        public string|Optional|null $password,
        #[
            RequiredIf('change_password', true),
            CurrentPassword('api')
        ]
        public string|Optional|null $current_password,
        public string|Optional|null $default_route,
        #[Min(1), Max(30)]
        public int|Optional $recent_notifications_limit,
        #[DataCollectionOf(UpdateNotificationSettingsGroupData::class)]
        public DataCollection|Optional $notification_settings,
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

    public static function messages(...$args): array
    {
        return [
            'current_password.current_password' => 'The current password is invalid.',
            'password.different' => 'The new password must be different from the current one.',
        ];
    }
}
