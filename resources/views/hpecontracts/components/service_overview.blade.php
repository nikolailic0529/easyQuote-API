<table class="table">
    <thead>
        <tr class="table-header">
            <th colspan="4"><h5>{{ $data->translations['service_overview'] ?? 'Service Overview' }}</h5></th>
        </tr>
        <tr>
            <th>{{ $data->translations['hpe_contract'] ?? 'HPE Contract' }}</th>
            <th>{{ $data->translations['date_from'] ?? 'Start Date'}}</th>
            <th>{{ $data->translations['date_to'] ?? 'End Date'}}</th>
            <th>{{ $data->translations['service_level'] ?? 'Service Level'}}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($table as $row)
        <tr>
            <td>{{ $row['contract_number'] ?? null }}</td>
            <td>{{ $row['contract_start_date'] ?? null }}</td>
            <td>{{ $row['contract_end_date'] ?? null }}</td>
            <td>{{ $row['service_description_2'] ?? null }}</td>
        </tr>
        @endforeach
    </tbody>
</table>