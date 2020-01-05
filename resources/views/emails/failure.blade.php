@component('mail::message')
# System failure report

The system went down with message:
```
{!! $failure->message !!}
```

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
{!! $failure->trace !!}
```

Thanks,<br>
{{ config('app.name') }}
@endcomponent

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.10/styles/github.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.10/highlight.min.js"></script>
<script>
    hljs.initHighlightingOnLoad();
</script>
