<?php

namespace App\Domain\Worldwide\View\Components\Template;

use Illuminate\View\Component;

class TemplateElement extends Component
{
    /**
     * Create a new component instance.
     *
     * @param \App\Domain\Template\DataTransferObjects\TemplateElementChild[] $children
     */
    public function __construct(public array $children,
                                public bool $hide)
    {
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        if ($this->hide) {
            return '';
        }

        return <<<'blade'
<div {{ $attributes->merge(["class" => "row form-group"]) }}>
    @foreach ($children as $child)
        <x-template.element-child class="{{ $child->class }}" style="{{ $child->css }}" :controls="$child->controls" />
    @endforeach
</div>
blade;
    }
}
