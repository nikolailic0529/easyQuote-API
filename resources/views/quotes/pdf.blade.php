<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
@if (isset($html) && $html)
    <link rel="stylesheet" href="{{ asset('css\bootstrap.min.css') }}"/>
@else
    <link rel="stylesheet" href="{{ public_path('css\bootstrap.min.css') }}"/>
@endif
<style>
    html, body {
        font-size: 18px;
    }
    h5,.h5 {
        font-size: 1.25rem
    }
    b {
        font-weight: 600
    }
    .blue-text {
        color: #1876bd;
        margin-bottom: .5rem;
        margin-top: 1.25rem
    }
    .page-break ~ .blue-text {
        margin-top: 0
    }
    .details {
        margin-bottom: 2rem;
    }
    .page-break {
        page-break-after: always;
    }
    .table {
        font-size: 14px;
        margin-bottom: 0;
        border-color: #1c6ea4;
        border-width: 2px;
        border-style: solid;
        color: #000
    }
    thead {
        display: table-row-group;
        background-color: #eee;
        font-size: .8888888888888889rem
    }
    tr {
        page-break-inside: avoid
    }
    .table-striped>tbody>tr:nth-of-type(odd) {
        background-color: #d0e4f5
    }
    .table-striped>tbody>tr:nth-of-type(even) {
        background-color: #eee
    }
    .table-bordered>thead>tr>th, .table-bordered>tbody>tr>th, .table-bordered>tfoot>tr>th, .table-bordered>thead>tr>td, .table-bordered>tbody>tr>td, .table-bordered>tfoot>tr>td {
        border-color: #aaa;
        border-bottom: 0
    }
</style>
<body>
    @include ('quotes.components.page', ['page_name' => 'first_page'])

    <div class="page-break"></div>

    <div class="row form-group">
        <div class="col-lg-12 border-right">
            <h5 class="blue-text">{{ __('Quotation Detail') }}</h5>
        </div>
    </div>
    <div class="details">
        @include ('quotes.components.page', ['page_name' => 'data_pages'])
    </div>

    @include ('quotes.components.data.table', ['page_name' => 'data_pages', 'data_key' => 'rows'])

    <div class="page-break"></div>

    @isset($data['payment_schedule']['data'])
        @include ('quotes.components.page', ['page_name' => 'payment_schedule'])
        @include ('quotes.components.data.table', ['page_name' => 'payment_schedule', 'data_key' => 'data'])
        <div class="page-break"></div>
    @endisset

    @include ('quotes.components.page', ['page_name' => 'last_page'])
</body>
