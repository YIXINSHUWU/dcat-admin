<?php

namespace Dcat\Admin\Grid\Column;

use Dcat\Admin\Widgets\Tooltip;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Str;

class Help implements Renderable
{
    /**
     * @var string
     */
    protected $message = '';

    /**
     * @var string
     */
    protected $style;

    /**
     * @var null
     */
    protected $placement;

    /**
     * Help constructor.
     *
     * @param string $message
     */
    public function __construct($message = '', ?string $style = null, ?string $placement = 'bottom')
    {
        $this->message = value($message);
        $this->style = $style;
        $this->placement = $placement;
    }

    /**
     * Render help  header.
     *
     * @return string
     */
    public function render()
    {
        $class = 'grid-column-help-'.Str::random(8);

        $tooltip = Tooltip::make('.'.$class);

        if (in_array($this->style, ['primary', 'green', 'blue', 'red', 'purple'])) {
            $tooltip->{$this->style}();
        }

        if (in_array($this->placement, ['bottom', 'left', 'right', 'top'])) {
            $tooltip->{$this->placement}();
        }

        $tooltip->content($this->message)->render();

        return <<<HELP
&nbsp;<a href="javascript:void(0);" class="{$class} fa fa-question-circle" ></a>
HELP;
    }
}