<div class="{{ $class }}">
    @foreach ($controls as $control)
        @php
            $controlType = $control['type'] ?? 'text';
        @endphp
        @includeFirst (["hpecontracts.components.{$controlType}", "hpecontracts.components.text"], $control)
    @endforeach
</div>
