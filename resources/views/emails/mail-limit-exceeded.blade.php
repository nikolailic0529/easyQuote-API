@component('mail::message')
# Mail limit exceeded

Monthly mail limit: {{ $limit }}
Remaining mail limit: {{ $remaining }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
