<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\{
    Arr,
    Facades\Http,
};

class Recaptcha implements Rule
{
    public string $version;

    public function __construct(string $version = 'v3')
    {
        $this->version = $version;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $data = [
            'secret' => config("services.recaptcha_{$this->version}.secret"),
            'response' => $value,
        ];

        report_logger(['message' => 'Google Recaptcha token validation.'], $data);

        $response = Http::asForm()->post(config("services.recaptcha_{$this->version}.url"), $data);

        $json = $response->json();

        if ($response->serverError()) {
            report_logger(['ErrorCode' => 'GRC_ERR_01'], ['ErrorDetails' => GRC_ERR_01], ['version' => $this->version, 'response' => $json, 'payload' => $data]);
            return false;
        }

        request()->request->set('recaptcha_response', $json);

        report_logger(['message' => 'Google Recaptcha response.'], $json);

        return
            app()->environment(['testing', 'local'])
            || Arr::get($json, 'success', false);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'We think you are a robot. Try again.';
    }
}
