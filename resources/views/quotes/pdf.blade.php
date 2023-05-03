<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @if (isset($html) && $html)
        <link rel="stylesheet" href="{{ asset('css\bootstrap.min.css') }}"/>
        <link rel="stylesheet" href="{{ asset('css\pdf.css') }}"/>
    @else
        <link rel="stylesheet" href="{{ public_path('css/bootstrap.min.css') }}"/>
        <link rel="stylesheet" href="{{ public_path('css/pdf.css') }}"/>
    @endif
</head>
<body>
@include ('quotes.components.page', ['page_name' => 'first_page'])

<div class="page-break"></div>

@include ('quotes.components.page', ['page_name' => 'data_pages'])

<div class="page-break"></div>
@isset($data['payment_schedule']['data'])
    @include ('quotes.components.page', ['page_name' => 'payment_schedule'])
    @include ('quotes.components.data.table', ['page_name' => 'payment_schedule', 'data_key' => 'data'])
    <div class="page-break"></div>
@endisset

@include ('quotes.components.page', ['page_name' => 'last_page'])
</body>
</html>
