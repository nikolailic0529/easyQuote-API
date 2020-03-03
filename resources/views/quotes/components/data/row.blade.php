<tr>
    @foreach ($data[$page_name]['rows_header'] ?? [] as $key => $header)
        @include ('quotes.components.data.field', ['field' => optional($row)[$key]])
    @endforeach
</tr>
