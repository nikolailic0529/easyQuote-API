<div style="font-size: 12px !important;font-family: sans-serif;">
        @foreach ($elements as $element)
        <x-template.template-element class="{{ $element->class }}" style="{{ $element->css }}"
                                     :children="$element->children" :hide="$element->_hidden"/>
    @endforeach
</div>