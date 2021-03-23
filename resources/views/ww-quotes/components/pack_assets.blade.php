<table class="table table-striped table-bordered">
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

                <td @if($field->field_name === 'price') style="white-space: nowrap" @endif >{{ $row->{$field->field_name} ?? '' }}</td>

            @endforeach

        </tr>

    @endforeach
    </tbody>
</table>
