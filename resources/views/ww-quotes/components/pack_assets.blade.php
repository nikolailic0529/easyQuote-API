<table class="table table-striped table-bordered" style="font-size:12px;">
    <thead>
    <tr>
        @foreach ($asset_fields as $field)
            <th style="white-space: nowrap">{{ $field->field_header }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>

    @foreach ($assets_data as $row)
        <tr>

            @foreach ($asset_fields as $field)

                <td @if(in_array($field->field_name, ['price', 'serial_no'])) style="white-space: nowrap" @endif >{{ $row->{$field->field_name} ?? '' }}</td>

            @endforeach

        </tr>

    @endforeach
    </tbody>
</table>

@if (filled($asset_notes))
    <p class="mt-1" style="white-space: pre-line;">{{ $asset_notes }}</p>
@endif

@if (!blank_html($additional_details))
    <div class="mt-2" style="white-space: pre-line;">{{ $additional_details }}</div>
@endif
