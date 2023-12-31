<?php

namespace App\Domain\Worldwide\View\Components\Template;

use Illuminate\View\Component;

class Control extends Component
{
    const TYPE_HDR = 'h';
    const TYPE_HR = 'hr';
    const TYPE_IMG = 'img';
    const TYPE_LBL = 'label';
    const TYPE_TAG = 'tag';
    const TYPE_TXT = 'text';

    public string $id;

    public string $type;

    public string $value;

    /**
     * Create a new component instance.
     */
    public function __construct(string $id, string $type, string $value)
    {
        $this->id = $id;
        $this->type = $type;
        $this->value = $value;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        switch ($this->type) {
            case self::TYPE_HDR:
                return <<<'blade'
<h5 {{ $attributes }}>{{ $value }}</h5>
blade;
            case self::TYPE_HR:
                return <<<'blade'
<hr />
blade;
            case self::TYPE_IMG:
                return <<<'blade'
<img src="{{ $value }}" {{ $attributes }} />
blade;
            case self::TYPE_LBL:
                return <<<'blade'
<span {{ $attributes }} >{{ $value }}</span>
blade;
            case self::TYPE_TAG:
                return <<<'blade'
<span {{ $attributes }}>{!! $value !!}</span>
blade;
            default:
            case self::TYPE_TXT:
                return <<<'blade'
 <span {{ $attributes }} >{!! $value !!}</span>
blade;
        }
    }
}
