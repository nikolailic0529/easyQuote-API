@foreach ($table as $contract)

<table class="table">
    <thead>
        <tr class="table-header">
            <th colspan="4">
                <h5>{{ $data->translations['contract_details'] ?? 'Contract Details'}}</h5>
            </th>
            <th class="text-right" colspan="4">
                {{ $data->translations['hpe_contract'] ?? 'HPE Contract'}}: {{ $contract['contract_number'] ?? null }}
            </th>
        </tr>
        <tr class="table-subheader">
            <td class="px-0" colspan="8">
                <table class="heading-table">
                    <tr>
                        <th>{{ $data->translations['date_from'] ?? 'Start Date'}}</th>
                        <td>{{ $contract['contract_start_date'] ?? null }}</td>
                        <th>{{ $data->translations['date_to'] ?? 'End Date'}}</th>
                        <td>{{ $contract['contract_end_date'] ?? null }}</td>
                    </tr>
                    <tr>
                        <th>{{ $data->translations['authorization'] ?? 'Purchase Order / Authorization' }}</th>
                        <td>{{ $contract['order_authorization'] ?? null }}</td>
                        <th>{{ $data->translations['authorization_date'] ?? 'Purchase Order / Authorization Date' }}</th>
                        <td>{{ $data->purchase_order_date ?? null }}</td>
                    </tr>
                    <tr>
                        <th>{{ $data->translations['hpe_sales_order'] ?? 'HPE Sales Order' }}</th>
                        <td> {{ $data->hpe_sales_order_no ?? null }} </td>
                        <th></th>
                        <td></td>
                    </tr>
                </table>
            </td>
        <tr>
            <th>{{ $data->translations['number'] ?? 'No.' }}</th>
            <th>{{ $data->translations['qty'] ?? 'Qty' }}</th>
            <th>{{ $data->translations['product_no'] ?? 'Product' }}</th>
            <th>{{ $data->translations['description'] ?? 'Description' }}</th>
            <th>{{ $data->translations['serial_no'] ?? 'Serial No' }}</th>
            <th>{{ $data->translations['support_account_reference'] ?? 'Support Account Reference' }}</th>
            <th>{{ $data->translations['date_from'] ?? 'Start Date' }}</th>
            <th>{{ $data->translations['date_to'] ?? 'End Date' }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($contract['assets_services'] ?? [] as $service)
        <tr>
            <td>----</td>
            <td>----</td>
            <th>{{ $service['service_code'] ?? null }}</td>
            <th colspan="5">{{ $service['service_description_2'] ?? null }}</td>
        </tr>
            @foreach ($service['assets'] as $asset)
            <tr>
                <td>{{ $asset->no ?? null }}</td>
                <td>{{ $asset->product_quantity ?? null }}</td>
                <td>{{ $asset->product_number ?? null }}</td>
                <td>{{ $asset->product_description ?? null }}</td>
                <td>{{ $asset->serial_number ?? null }}</td>
                <td>{{ $asset->support_account_reference ?? null }}</td>
                <td>{{ $asset->support_start_date ?? null }}</td>
                <td>{{ $asset->support_end_date ?? null }}</td>
            </tr>
            @endforeach
        @endforeach
    </tbody>
</table>

<div class="page-break"></div>

@endforeach