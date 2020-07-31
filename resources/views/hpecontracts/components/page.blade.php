@foreach ($page ?? [] as $item)
    <div class="row form-group">
        @foreach ($item['child'] ?? [] as $child)
            @include('hpecontracts.components.child', $child)
        @endforeach
    </div>
@endforeach
