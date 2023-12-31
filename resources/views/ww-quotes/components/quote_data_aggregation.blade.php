<table class="table table-striped table-bordered" style="font-size: 12px;">
    <thead>
    <tr>
        @foreach ($aggregation_fields as $field)
            <th>{{ $field->field_header }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>

    @foreach ($aggregation_data as $row)
        <tr>

            @foreach ($aggregation_fields as $field)

                <td>{{ $row->{$field->field_name} ?? '' }}</td>

            @endforeach

        </tr>

    @endforeach
    <tr>
        <td class="text-right" colspan="3">
            {{ rtrim($headers['sub_total'] ?? 'Sub Total', ':') }}:
        </td>
        <td>{{ $sub_total_value }}</td>
    </tr>

    @unless($sub_total_value === $total_value_including_tax)
    <tr>
        <td class="text-right" colspan="3">
            {{ rtrim($headers['total_including_tax'] ?? 'Total (inc. tax)', ':') }}:
        </td>
        <td>{{ $total_value_including_tax }}</td>
    </tr>
    @endunless
    <tr>
        <td class="text-right" colspan="3">
            {{ rtrim($headers['grand_total'] ?? 'Grand Total', ':') }}:
        </td>
        <td>{{ $grand_total_value }}</td>
    </tr>
    </tbody>
</table>
