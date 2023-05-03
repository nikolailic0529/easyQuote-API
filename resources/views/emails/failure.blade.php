@component('mail::message')
# System failure report

The system went down with message:
```
{!! $failure_message !!}
```

**Trace:**
```
{!! $failure_trace !!}
```

Thanks,<br>
{{ config('app.name') }}
@endcomponent

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.10/styles/github.min.css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.15.10/highlight.min.js"></script>
<script>
    hljs.initHighlightingOnLoad();
</script>
