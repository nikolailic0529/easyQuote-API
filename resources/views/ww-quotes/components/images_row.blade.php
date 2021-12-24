@foreach ($images as $image)
  <img class="{{ $class ?? '' }}" src="{{ $image }}"  alt=""/>
@endforeach
