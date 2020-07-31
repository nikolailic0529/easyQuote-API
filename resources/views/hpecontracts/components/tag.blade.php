@php
    $value = data_get($data, $id) ?? str_repeat('-', 15);
@endphp

@if (is_iterable($value))
    @includeFirst(["hpecontracts.components.{$id}", "hpecontracts.components.table"], ['table' => $value])
@else
    <span class="{{ $class }}">{{ $value }}</span>
@endif
