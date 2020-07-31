<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
</head>

<body>
    @if ($web ?? false)
    <link rel="stylesheet" href="{{ asset('css\bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css\hpe.pdf.css') }}" />
    @else
    <link rel="stylesheet" href="{{ public_path('css\bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ public_path('css\hpe.pdf.css') }}" />
    @endif

    @foreach ($form as $page)
    @include('hpecontracts.components.page', ['page' => $page])

    @if (! $loop->last)
    <div class="page-break"></div>
    @endif

    @endforeach

</html>