<table class="table table-striped table-bordered">
    <thead>
        <tr>
            {{--  --}}
        </tr>
    </thead>
    <tbody>
        {{-- @isset (head($data[$page_name][$data_key])['rows'])
            @foreach ($data[$page_name][$data_key] ?? [] as $group)
                @include('quotes.components.data.group', compact('group'))
            @endforeach
        @else
            @foreach ($data[$page_name][$data_key] ?? [] as $row)
                @include ('quotes.components.data.row', compact('row'))
            @endforeach
        @endisset --}}
    </tbody>
</table>
