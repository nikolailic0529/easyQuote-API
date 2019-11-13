<table class="table table-striped table-bordered">
    <thead>
        <tr>
            @each ('quotes.components.data.header', $data[$page_name]['rows_header'] ?? [], 'header')
        </tr>
    </thead>
    <tbody>
        @isset (head($data[$page_name][$data_key])['rows'])
            @each ('quotes.components.data.group', $data[$page_name][$data_key] ?? [], 'group')
        @else
            @each ('quotes.components.data.row', $data[$page_name][$data_key] ?? [], 'row')
        @endisset
    </tbody>
</table>
