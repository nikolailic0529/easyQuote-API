<?php

namespace App\View\Components\Template;

use App\DTO\Template\TemplateElementChild;
use Illuminate\View\Component;

class TemplateElement extends Component
{
    public array $children;

    /**
     * Create a new component instance.
     *
     * @param \App\DTO\Template\TemplateElementChild[] $children
     */
    public function __construct(array $children)
    {
        $this->children = $children;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return <<<'blade'
<div {{ $attributes->merge(["class" => "row form-group"]) }}>
    @foreach ($children as $child)
        <x-template.element-child class="{{ $child->class }}" style="{{ $child->css }}" :controls="$child->controls" />
    @endforeach
</div>
blade;

    }
}
