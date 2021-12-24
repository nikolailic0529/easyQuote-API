@extends('ww-quotes.layout.pdf')

@section('content')
    @foreach ($elements as $element)
        <x-template.template-element class="{{ $element->class }}" style="{{ $element->css }}" :children="$element->children" :hide="$element->_hidden" />
    @endforeach
@endsection