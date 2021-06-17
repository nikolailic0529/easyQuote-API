<table class="table table-striped table-bordered">
    <thead>
    <tr>
        @foreach ($asset_fields as $field)
            <th style="white-space: nowrap">{{ $field->field_header }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>

    @foreach ($assets_data as $line)
        @foreach ($line->assets as $row)
            @if ($loop->first)
                <tr>
                    <td class="br-0 bold"
                        colspan="{{ count($asset_fields) - 2 }}">{{ $line->group_name }}</td>
                    <td class="bl-0" colspan="2" style="text-align:right">Total: <span
                            class="bold">{{ $line->group_total_price }}</span></td>
                </tr>
            @endif
            <tr>
                @foreach ($asset_fields as $field)

                    <td
                        @if ($field->field_name === 'price')style="white-space: nowrap"@endif
                    >{{ $row->{$field->field_name} ?? "" }}</td>

                @endforeach
            </tr>
        @endforeach
    @endforeach
    </tbody>
</table>
