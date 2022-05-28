<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="stylesheet" href="{{ public_path('css/bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ public_path('css/pdf.css') }}"/>

    <style>
        @php
            $grid_gutter_width = 15;
        @endphp

        .row {
            margin-right: -{{ $grid_gutter_width / 2 }}px;
            margin-left: -{{ $grid_gutter_width / 2 }}px;
        }
        .col-xs-1, .col-sm-1, .col-md-1, .col-lg-1, .col-xs-2, .col-sm-2, .col-md-2, .col-lg-2, .col-xs-3, .col-sm-3, .col-md-3, .col-lg-3, .col-xs-4, .col-sm-4, .col-md-4, .col-lg-4, .col-xs-5, .col-sm-5, .col-md-5, .col-lg-5, .col-xs-6, .col-sm-6, .col-md-6, .col-lg-6, .col-xs-7, .col-sm-7, .col-md-7, .col-lg-7, .col-xs-8, .col-sm-8, .col-md-8, .col-lg-8, .col-xs-9, .col-sm-9, .col-md-9, .col-lg-9, .col-xs-10, .col-sm-10, .col-md-10, .col-lg-10, .col-xs-11, .col-sm-11, .col-md-11, .col-lg-11, .col-xs-12, .col-sm-12, .col-md-12, .col-lg-12 {
            padding-right: {{ $grid_gutter_width / 2 }}px;
            padding-left: {{ $grid_gutter_width / 2 }}px;
        }

        html, body {
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    @yield('content')
</div>

</body>
</html>
