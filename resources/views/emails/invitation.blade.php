@component('mail::message')
# Invitation for easyQuote Collaboration

You have been invited by {{ $user_email }} for collaboration as {{ $role_name }}.

@component('mail::button', ['url' => $url])
Complete registration
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
