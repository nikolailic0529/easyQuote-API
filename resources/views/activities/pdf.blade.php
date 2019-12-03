<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="stylesheet" href="{{ public_path('css\bootstrap.min.css') }}"/>
    <link rel="stylesheet" href="{{ public_path('css\pdf.css') }}"/>
</head>
<body>
    @isset($activityCollection->subjectName)
        <h1>{{ $activityCollection->subjectName }}</h1>
        <hr/>
    @endisset
    <h1>Summary</h1>

    <table class="table table table-striped table-bordered">
        <thead>
            <tr>
                @foreach ($activityCollection->summaryHeader as $summaryHeader)
                    <td>{{ $summaryHeader }}</td>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach ($activityCollection->summaryData as $summaryData)
                    <td>{{ $summaryData }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <hr/>

    <h1>Logs</h1>
    <table class="table table table-striped table-bordered">
        <thead>
            <tr>
                <td>Subject</td>
                <td>Subject Type</td>
                <td>Description</td>
                <td colspan="2">Previous</td>
                <td colspan="2">Current</td>
                <td>Action By</td>
                <td>On</td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
                <td>Attribute</td>
                <td>Value</td>
                <td>Attribute</td>
                <td>Value</td>
                <td></td>
                <td></td>
        </thead>
        <tbody>
            @foreach ($activityCollection->collection as $activity)
                @foreach ($activity as $row)
                    <tr>
                        @foreach ($row as $value)
                            <td>{{ $value }}</td>
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
</body>
</html>
