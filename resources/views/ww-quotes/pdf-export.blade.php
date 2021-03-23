@extends('ww-quotes.layout.pdf')

@section('content')
    @foreach ($template_data->first_page_schema as $element)
        <x-template.template-element class="{{ $element->class }}" style="{{ $element->css }}" :children="$element->children"/>
    @endforeach

    <div class="page-break"></div>

    @foreach ($template_data->assets_page_schema as $element)
        <x-template.template-element class="{{ $element->class }}" style="{{ $element->css }}" :children="$element->children" />
    @endforeach

    <div class="page-break"></div>

    @foreach ($template_data->payment_schedule_page_schema as $element)
        <x-template.template-element class="{{ $element->class }}" style="{{ $element->css }}" :children="$element->children" />
    @endforeach

    <div class="page-break"></div>

    @foreach ($template_data->last_page_schema as $element)
        <x-template.template-element class="{{ $element->class }}" style="{{ $element->css }}" :children="$element->children" />
    @endforeach

@endsection
