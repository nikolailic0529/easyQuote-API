@foreach ($table as $contract)

<table class="table">
    <thead>
        <tr class="table-header"><th colspan="5"><h5>{{ $data->translations['serial_number_details'] ?? 'Serial Number Details' }}</h5></th></tr>
        <tr class="table-subheader"><th colspan="5">{{ $data->translations['hpe_contract'] ?? 'HPE Contract' }}: {{ $contract['contract_number'] ?? null }}</th></tr>
        <tr>
            <th>{{ $data->translations['qty'] ?? 'Qty'}}</th>
            <th>{{ $data->translations['product_no'] ?? 'Product'}}</th>
            <th>{{ $data->translations['description'] ?? 'Description'}}</th>
            <th>{{ $data->translations['serial_no'] ?? 'Serial No'}}</th>
            <th>{{ $data->translations['support_account_reference'] ?? 'Support Account Reference'}}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($contract['assets'] ?? [] as $asset)
        <tr>
            <td>{{ $asset->product_quantity ?? null }}</td>
            <td>{{ $asset->product_number ?? null }}</td>
            <td>{{ $asset->product_description ?? null }}</td>
            <td>{{ $asset->serial_number ?? null }}</td>
            <td>{{ $asset->support_account_reference ?? null }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="page-break"></div>

@endforeach