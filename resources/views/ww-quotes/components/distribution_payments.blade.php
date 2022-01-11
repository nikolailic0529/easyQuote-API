<table class="table table-striped table-bordered" style="font-size:12px;">
    <thead>
    <tr>
        @foreach ($payment_schedule_fields as $field)
            <th>{{ $field->field_header }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>

    @foreach ($payment_schedule_data as $row)
        <tr>

            @foreach ($payment_schedule_fields as $field)

                <td @if($field->field_name === 'price') style="white-space: nowrap" @endif >{{ $row->{$field->field_name} ?? '' }}</td>

            @endforeach

        </tr>

    @endforeach
    </tbody>
</table>
