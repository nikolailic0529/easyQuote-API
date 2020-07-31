@foreach ($table as $contract)

<table class="table mb-3">
    <thead>
        <tr class="table-header table-header-bordered">
            <th colspan="4">
                <h5>{{ $data->translations['support_service_details'] ?? 'Support Service Details' }}</h5>
            </th>
            <th class="text-right" colspan="4">
                {{ $data->translations['hpe_contract'] ?? 'HPE Contract' }}: {{ $contract['contract_number'] ?? null }}
            </th>
        </tr>
    </thead>
    <tbody class="border-none">
        @foreach ($contract['services'] ?? [] as $service)
        <tr><td class="pb-1"></td></tr>
        <tr>
            <th>{{ $service->no ?? null }}</th>
            <th>{{ $service->service_code_2 ?? null }}</th>
            <th>{{ $service->service_description ?? null }}</th>
        </tr>
        <tr>
            <th colspan="4">&emsp;&emsp;&emsp;{{ $service->service_description_2 ?? null }}</th>
        </tr>
        @foreach ($service->service_levels ?? [] as $level)
        <tr>
            <td class="py-0" colspan="4">&emsp;&emsp;&emsp;&emsp;&emsp;{{ $level }}</td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>

@endforeach