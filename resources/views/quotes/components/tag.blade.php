@php
    $tagValue = trim(data_get($data, "{$page_name}.{$id}") ?? str_repeat('-', 15));
@endphp
<span
    class="{{ $class }}"
    @textoverflow ($tagValue, 15, $page_name === 'data_pages')
        style="font-size:14px!important"
    @endtextoverflow
    >{{ $tagValue }}</span>
