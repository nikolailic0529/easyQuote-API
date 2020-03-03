@foreach ($group['rows'] as $row)
    @if ($loop->first)
        <tr>
            <td class="br-0 bold" colspan="{{ $group['headers_count'] - (isset($group['total_price']) ? 2 : 0) }}">{{ $group['name'] ?? null }}</td>
            @isset ($group['total_price'])
                <td class="bl-0" colspan="2" style="text-align:right">Total: <span class="bold">{{ $group['total_price'] ?? 0 }}</span></td>
            @endisset
        </tr>
    @endif
    <tr>
        @foreach ($data[$page_name]['rows_header'] as $key => $header)
            @include ('quotes.components.data.field', ['field' => optional($row)[$key]])
        @endforeach
    </tr>
@endforeach
