<?php

namespace App\View\Components\Template;

use App\DTO\Template\TemplateElementChildControl;
use Illuminate\View\Component;

class ElementChild extends Component
{
    public array $controls;

    /**
     * Create a new component instance.
     *
     * @param \App\DTO\Template\TemplateElementChildControl[] $controls
     */
    public function __construct(array $controls)
    {
        $this->controls = $controls;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return <<<'blade'
<div {{ $attributes }}>
    @foreach ($controls as $control)
        <x-template.control :id="$control->id" class="{{ $control->class }}" style="{{ $control->css }}" :type="$control->type" :value="$control->value" />
    @endforeach
</div>
blade;
    }
}
