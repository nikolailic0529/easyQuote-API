<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link rel="stylesheet" href="{{ public_path('css\bootstrap.min.css') }}"/>
<style>
    html {
        font-size: 16px;
        font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
    }
    body {
        font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
    }
    .h1, .h2, .h3, .h4, .h5, .h6, h1, h2, h3, h4, h5, h6 {
        margin-bottom: .5rem;
        font-weight: 500;
        line-height: 1.2;
        font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
    }
    h5,.h5 {
        font-size: 1.09375rem
    }
    .blue-text {
        color: #1876bd;
    }
    .dashed-border {
        border: 1px dashed #dedede;
        padding: 10px;
        margin-bottom: 20px;
        font-size: .75rem;
    }
    .page-break {
        page-break-after: always;
    }
    thead {
        display: table-header-group
    }
    tfoot {
        display: table-row-group
    }
    tr {
        page-break-inside: avoid
    }
</style>
<body>
    @include ('quotes.components.page', ['page_name' => 'first_page'])

    <div class="page-break"></div>

    <h5 class="blue-text">{{ __('Quotation Detail') }}</h5>
    <div class="dashed-border">
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
