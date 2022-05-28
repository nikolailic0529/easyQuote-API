<div style="font-size: .85rem !important;">
    @foreach ($elements as $element)
        <x-template.template-element class="{{ $element->class }}" style="{{ $element->css }}"
                                     :children="$element->children" :hide="$element->_hidden"/>
    @endforeach
</div>