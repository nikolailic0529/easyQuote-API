<table class="table">
    <thead>
        <tr>
            <th>{{ $data->translations['hpe_contract'] ?? 'HPE Contract' }}</th>
            <th>{{ $data->translations['date_from'] ?? 'Start Date' }}</th>
            <th>{{ $data->translations['date_to'] ?? 'End Date' }}</th>
            <th>{{ $data->translations['hpe_sales_order'] ?? 'HPE Sales Order' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($table as $row)
        <tr>
            <td>{{ $row['contract_number'] ?? null }}</td>
            <td>{{ $row['contract_start_date'] ?? null }}</td>
            <td>{{ $row['contract_end_date'] ?? null }}</td>
            <td>{{ $row['hpe_sales_order_no'] ?? null }}</td>
        </tr>
        @endforeach
    </tbody>
</table>