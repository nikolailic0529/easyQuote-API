<div class="{{ $class }}">
    @foreach ($controls as $control)
        @includeFirst (["quotes.components.{$control['type']}", "quotes.components.text"], $control)
    @endforeach
</div>
