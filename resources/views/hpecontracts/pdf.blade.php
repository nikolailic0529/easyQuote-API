<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    @if ($web ?? false)
    <link rel="stylesheet" href="{{ asset('css\bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('css\hpe.pdf.css') }}" />
    @else
    <link rel="stylesheet" href="{{ public_path('css\bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ public_path('css\hpe.pdf.css') }}" />
    @endif

    @if (isset($orientation))
        @if ($orientation === 'L' || $orientation === 'Landscape')
            <style>
                html {
                    width: 319.4mm;
                    height: 215.9mm;
                    margin: 0;
                }
            </style>
        @else
            <style>
                html {
                    width: 215.9mm;
                    height: 279.4mm;
                    margin: 0;
                }
            </style>
        @endif
    @endif
</head>

<body>

    @foreach ($form as $key => $page)
    @include('hpecontracts.components.page', ['page' => $page])

    @if (! $loop->last)
    <div class="page-break"></div>
    @endif

    @endforeach

    <script>
        // Handling overflowed columns.
        document.addEventListener("DOMContentLoaded", function (event) {
            var overflowThreshold = 120;

            var columns = document.querySelectorAll('.row > [class*=col-]');

            for (var i = 0; i < columns.length; i++) {
                var element = columns[i];

                if (element.scrollWidth > element.offsetWidth + 15) {
                    var diff = Math.abs(element.scrollWidth - element.offsetWidth);

                    if (diff > overflowThreshold) {
                        continue;
                    }

                    var paddingLeft = parseInt(window.getComputedStyle(element).getPropertyValue('padding-left'));

                    element.style.width = (element.scrollWidth + paddingLeft) + 'px'
                }
            }

            document.documentElement.style.width = 'auto';
            document.documentElement.style.height = 'auto';
        });

    </script>

</html>
