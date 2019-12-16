@component('mail::message')
# System failure report

The system went down with message: `{{ $failure->message }}`.

**Possible reasons:**
@forelse ($failure->reasons as $key => $reason)
     {{ ++$key }}. {{ $reason }}
@empty
     No known reasons.
@endforelse

**Possbile resolving:**
@forelse ($failure->resolving as $key => $resolve)
    {{ ++$key }}. {{ $resolve }}
@empty
    No known resovling.
@endforelse

**Trace:**
```
{{ $failure->trace }}
```

Thanks,<br>
{{ config('app.name') }}
@endcomponent
