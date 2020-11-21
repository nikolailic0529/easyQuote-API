
@foreach ($table as $sar)
    <table class="table">
        <thead>
            <tr class="table-header">
                <th colspan="6"><h5>{{ $data->translations['support_account_reference_detail'] ?? 'Support Account Reference Detail' }}</h5></th>
            </tr>
            <tr class="table-subheader">
                <th colspan="6">
                    {{ $data->translations['support_account_reference'] ?? 'Support Account Reference' }}: {{ $sar['support_account_reference'] ?? null }}
                    <br /><span class="text-normal">{{ $data->end_customer_contact->org_name }}</span>
                </th>
            </tr>
            <tr>
                <th>{{ $data->translations['qty'] ?? 'Qty' }}</th>
                <th>{{ $data->translations['product_no'] ?? 'Product' }}</th>
                <th>{{ $data->translations['description'] ?? 'Description' }}</th>
                <th>{{ $data->translations['serial_no'] ?? 'Serial No' }}</th>
                <th>{{ $data->translations['hpe_contract'] ?? 'HPE Contract' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sar['assets'] ?? [] as $asset)
            <tr>
                <td>{{ $asset->product_quantity ?? null }}</td>
                <td>{{ $asset->product_number ?? null }}</td>
                <td>{{ $asset->product_description ?? null }}</td>
                <td>{{ $asset->serial_number ?? null }}</td>
                <td>{{ $asset->contract_number ?? null }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="page-break"></div>

@endforeach