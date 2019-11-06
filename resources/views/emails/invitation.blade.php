@component('mail::message')
# You are invited!

You have been invited to join easyQuote.

@component('mail::button', ['url' => $url])
Complete registration
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
