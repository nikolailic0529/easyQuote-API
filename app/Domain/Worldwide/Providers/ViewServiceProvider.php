<?php

namespace App\Domain\Worldwide\Providers;

use App\Domain\Worldwide\View\Components\Template\Control;
use App\Domain\Worldwide\View\Components\Template\ElementChild;
use App\Domain\Worldwide\View\Components\Template\TemplateElement;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::component(Control::class, 'template.control');
        Blade::component(ElementChild::class, 'template.element-child');
        Blade::component(TemplateElement::class, 'template.template-element');
    }
}
