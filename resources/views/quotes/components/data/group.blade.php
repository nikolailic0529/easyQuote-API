@foreach ($group['rows'] as $row)
    @if ($loop->first)
        <tr>
            <td class="br-0 bold" colspan="{{ $group['headers_count'] - 2 }}">{{ $group['name'] ?? null }}</td>
            <td class="br-0 bl-0">Total Count: <span class="bold">{{ $group['total_count'] ?? 0 }}</span></td>
            <td class="bl-0">Total Price: <span class="bold">{{ $group['total_price'] ?? 0 }}</span></td>
        </tr>
    @endif
    <tr>
        @each ('quotes.components.data.field', (array) $row, 'field')
    </tr>
@endforeach
