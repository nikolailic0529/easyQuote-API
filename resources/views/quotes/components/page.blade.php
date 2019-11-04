@foreach ($design[$page_name] as $row)
    <div class="row">
        @foreach ($row['child'] as $child)
            @include('quotes.components.child', $child)
        @endforeach
    </div>
@endforeach
