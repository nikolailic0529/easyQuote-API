<?php

namespace App\Domain\User\Requests;

use App\Domain\Company\Enum\CompanyType;
use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\Timezone\Models\Timezone;
use App\Domain\User\DataTransferObjects\UpdateCurrentUserData;
use App\Foundation\DataTransferObject\Enum\DataTransferValueOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrentUserRequest extends FormRequest
{
    protected ?UpdateCurrentUserData $updateCurrentProfileData = null;

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'first_name' => ['string', 'min:2', 'alpha_spaces'],
            'middle_name' => ['nullable', 'string', 'alpha_spaces'],
            'last_name' => ['string', 'min:2', 'alpha_spaces'],
            'email' => ['string', 'email', Rule::unique('users')->ignore($this->user())->withoutTrashed()],
            'phone' => ['nullable', 'string', 'min:4', 'phone'],
            'timezone_id' => ['uuid', Rule::exists(Timezone::class, 'id')],
            'country_id' => ['uuid', Rule::exists(Country::class, 'id')->withoutTrashed()],
            'company_id' => [
                'nullable', 'uuid',
                Rule::exists(Company::class, 'id')
                    ->where('type', CompanyType::INTERNAL)
                    ->withoutTrashed(),
            ],
            'hpe_contract_template_id' => [
                'nullable', 'uuid',
                Rule::exists(HpeContractTemplate::class, 'id')
                    ->withoutTrashed(),
            ],
            'picture' => ['image', 'max:2048'],
            'delete_picture' => ['nullable', 'boolean'],
            'change_password' => ['nullable', 'boolean'],
            'current_password' => [
                Rule::requiredIf(fn () => $this->boolean('change_password')),
                'string', 'password:api',
            ],
            'password' => [
                Rule::requiredIf(fn () => $this->boolean('change_password')),
                'string', 'min:8', 'different:current_password', 'confirmed',
            ],
            'default_route' => ['nullable', 'string', 'max:200'],
            'recent_notifications_limit' => ['integer', 'min:1', 'max:30'],
        ];
    }

    public function messages(): array
    {
        return [
            'sales_unit_id.required' => 'Sales Unit must be selected.',
            'password.regex' => 'The Password should contain uppercase and lowercase characters, digits, non-alphanumeric characters.',
            'current_password.password' => 'You have entered invalid current password.',
            'password.different' => "Your new password shouldn't be same as your last password",
            'first_name.min' => 'The first name/last name must be of at least :min characters.',
            'last_name.min' => 'The first name/last name must be of at least :min characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(
            $this->collect(['phone', 'middle_name', 'hpe_contract_template_id'])
                ->map(static function (mixed $value): mixed {
                    return 'null' === $value ? null : $value;
                })
                ->all()
        );
    }

    public function getUpdateCurrentProfileData(): UpdateCurrentUserData
    {
        return $this->updateCurrentProfileData ??= new UpdateCurrentUserData([
            'first_name' => $this->input('first_name', DataTransferValueOption::Miss),
            'middle_name' => $this->input('middle_name', DataTransferValueOption::Miss),
            'last_name' => $this->input('last_name', DataTransferValueOption::Miss),
            'phone' => $this->input('phone', DataTransferValueOption::Miss),
            'timezone_id' => $this->input('timezone_id', DataTransferValueOption::Miss),
            'country_id' => $this->input('country_id', DataTransferValueOption::Miss),
            'picture' => $this->file('picture', DataTransferValueOption::Miss),
            'delete_picture' => $this->boolean('delete_picture', DataTransferValueOption::Miss),
            'change_password' => $this->boolean('change_password', DataTransferValueOption::Miss),
            'password' => $this->input('password', DataTransferValueOption::Miss),
            'default_route' => $this->input('default_route', DataTransferValueOption::Miss),
            'recent_notifications_limit' => $this->has('recent_notifications_limit')
                ? transform($this->input('recent_notifications_limit'), static fn (mixed $v): int => (int) $v)
                : DataTransferValueOption::Miss,
        ]);
    }
}
