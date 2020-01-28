@foreach ($group['rows'] as $row)
    @if ($loop->first)
        <tr>
            <td class="br-0 bold" colspan="{{ $group['headers_count'] - 3 }}">{{ $group['name'] ?? null }}</td>
            <td class="br-0 bl-0" colspan="{{ isset($group['total_price']) ? 1 : 3 }}">Count: <span class="bold">{{ $group['total_count'] ?? 0 }}</span></td>
            @isset ($group['total_price'])
                <td class="bl-0" colspan="2">Total: <span class="bold">{{ $group['total_price'] ?? 0 }}</span></td>
            @endisset
        </tr>
    @endif
    <tr>
        @each ('quotes.components.data.field', (array) $row, 'field')
    </tr>
@endforeach
