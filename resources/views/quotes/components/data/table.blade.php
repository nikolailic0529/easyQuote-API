<table class="table table-striped">
    <thead>
        <tr>
            @each ('quotes.components.data.header', $data[$page_name]['rows_header'] ?? [], 'header')
        </tr>
    </thead>
    <tbody>
        @each ('quotes.components.data.row', $data[$page_name][$data_key] ?? [], 'row')
    </tbody>
</table>
