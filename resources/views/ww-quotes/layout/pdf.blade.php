<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <link rel="stylesheet" href="{{ public_path('css/bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ public_path('css/pdf.css') }}"/>
</head>
<body>

<div class="container-fluid">
    @yield('content')
</div>

</body>
</html>
