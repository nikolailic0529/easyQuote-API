<div class="{{ $class }}">
    <div style="margin-top: 1.25rem;"></div>
    @include ('quotes.components.data.table', ['page_name' => 'data_pages', 'data_key' => 'rows'])

    @isset ($data['data_pages']['additional_details'])
        <div class="mt-2">{!! $data['data_pages']['additional_details'] !!}</div>
    @endisset
</div>