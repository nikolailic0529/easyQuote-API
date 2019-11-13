@foreach ($group['rows'] as $row)
    <tr>
        @if ($loop->first)
            <td rowspan="{{ count($group['rows']) }}">{{ $group['name'] }}</td>
        @endif
        @each ('quotes.components.data.field', (array) $row, 'field')
    </tr>
@endforeach
