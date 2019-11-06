@component('mail::message')
# System failure report

The system went down with following message.

{{ $message }}

Trace:
{{ $trace }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
