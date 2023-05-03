<?php

namespace App\Domain\Worldwide\View\Components\Template;

use Illuminate\View\Component;

class ElementChild extends Component
{
    public array $controls;

    /**
     * Create a new component instance.
     *
     * @param \App\Domain\Template\DataTransferObjects\TemplateElementChildControl[] $controls
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
