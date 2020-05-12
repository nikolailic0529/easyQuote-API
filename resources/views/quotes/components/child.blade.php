<div class="{{ $class }}">
    @foreach ($controls as $control)
        @php
            $controlType = $control['type'] ?? 'text';
        @endphp
        @includeFirst (["quotes.components.{$controlType}", "quotes.components.text"], $control)
    @endforeach
</div>
