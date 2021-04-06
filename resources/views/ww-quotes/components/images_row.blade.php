<div class="{{ $class ?? '' }}">
    @foreach ($images as $image)
        <img src="{{ $image }}"  alt=""/>
    @endforeach
</div>
