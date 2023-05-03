<table class="table table-striped table-bordered" style="font-size:12px;">
    <thead>
        <tr>
            @each ('quotes.components.data.header', $data[$page_name]['rows_header'], 'header')
        </tr>
    </thead>
    <tbody>
        @isset (head($data[$page_name][$data_key])['rows'])
            @foreach ($data[$page_name][$data_key] ?? [] as $group)
                @include('quotes.components.data.group', compact('group'))
            @endforeach
        @else
            @foreach ($data[$page_name][$data_key] ?? [] as $row)
                @include ('quotes.components.data.row', compact('row'))
            @endforeach
        @endisset
    </tbody>
</table>
