<?php

namespace Dcat\Admin\Widgets\Sparkline;

use Dcat\Admin\Admin;
use Dcat\Admin\Traits\FromApi;
use Dcat\Admin\Widgets\Widget;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

/**
 * @see https://omnipotent.net/jquery.sparkline
 *
 * @method $this fillColor(string $color)
 * @method $this lineColor(string $color)
 * @method $this chartRangeMin(int $val)
 * @method $this chartRangeMax(int $val)
 * @method $this enableTagOptions(bool $bool)
 * @method $this tagOptionPrefix(string $val)
 * @method $this tagValuesAttribute(string $val = 'values')
 * @method $this disableHiddenCheck(string $val)
 */
class Sparkline extends Widget
{
    use FromApi;

    public static $js = '@jquery.sparkline';
    public static $css = '@jquery.sparkline';

    protected static $optionMethods = [
        'highlightSpotColor',
        'highlightLineColor',
        'minSpotColor',
        'maxSpotColor',
        'spotColor',
        'lineWidth',
        'spotRadius',
        'normalRangeMin',
        'drawNormalOnTop',
        'xvalues',
        'chartRangeClip',
        'chartRangeMinX',

        'barColor',
        'negBarColor',
        'zeroColor',
        'nullColor',
        'barWidth',
        'zeroAxis',
        'colorMap',

        'targetColor',
        'targetWidth',
        'rangeColors',
        'performanceColor',

        'offset',
        'borderWidth',
        'borderColor',
        'sliceColors',

        'lineColor',
        'fillColor',
        'chartRangeMin',
        'chartRangeMax',
        'enableTagOptions',
        'tagOptionPrefix',
        'tagValuesAttribute',
        'disableHiddenCheck',
    ];

    protected $type = 'line';

    /**
     * @var array
     */
    protected $options = ['width' => '100%'];

    protected $values = [];

    protected $combinations = [];

    public function __construct($values = [])
    {
        $this->values($values);

        $this->options['type'] = $this->type;
    }

    /**
     * Get or set the sparkline values.
     *
     * @param mixed|null $values
     *
     * @return $this|array
     */
    public function values($values = null)
    {
        if ($values === null) {
            return $this->values;
        }

        if (is_string($values)) {
            $values = explode(',', $values);
        } elseif ($values instanceof Arrayable) {
            $values = $values->toArray();
        }

        $this->values = $values;

        return $this;
    }

    /**
     * Set width of sparkline.
     *
     * @param int $width
     *
     * @return $this
     */
    public function width($width)
    {
        $this->options['width'] = $width;

        return $this;
    }

    /**
     * Set height of sparkline.
     *
     * @param int $width
     *
     * @return $this
     */
    public function height($height)
    {
        $this->options['height'] = $height;

        $this->style('height:'.$height);

        return $this;
    }

    /**
     * Composite the given sparkline.
     *
     * @param int $width
     *
     * @return $this
     */
    public function combine(self $chart)
    {
        $this->combinations[] = [$chart->values(), $chart->getOptions()];

        return $this;
    }

    /**
     * Setup scripts.
     *
     * @param int $width
     *
     * @return string
     */
    protected function script()
    {
        $values = json_encode($this->values);
        $options = json_encode($this->options);

        if (! $this->allowBuildRequest()) {
            return <<<JS
$('#{$this->id}').sparkline($values, $options);
{$this->buildCombinationScript()};
JS;
        }

        $this->fetched(
            <<<JS
if (!response.status) {
    return Dcat.error(response.message || 'Server internal error.');
}        
var id = '{$this->id}', opt = $options;
opt = $.extend(opt, response.options || {});
$('#'+id).sparkline(response.values || $values, opt);
JS
        );

        return $this->buildRequestScript();
    }

    /**
     * @return string
     */
    protected function buildCombinationScript()
    {
        $script = '';

        foreach ($this->combinations as $value) {
            $value = json_encode($value[0]);
            $options = json_encode($value[1]);

            $script .= <<<JS
$('#{$this->id}').sparkline($value, $options);
JS;
        }

        return $script;
    }

    /**
     * @return string
     */
    public function render()
    {
        $this->makeId();

        Admin::script($this->script());

        $this->setHtmlAttribute([
            'id' => $this->id,
        ]);

        $this->collectAssets();

        return <<<HTML
<span {$this->formatHtmlAttributes()}></span>
HTML;
    }

    /**
     * Get element id.
     *
     * @return string
     */
    public function getId()
    {
        $this->makeId();

        return $this->id;
    }

    /**
     * @param string $method
     * @param array  $parameters
     *
     * @return Sparkline|Widget
     */
    public function __call($method, $parameters)
    {
        if (in_array($method, static::$optionMethods)) {
            return $this->options([$method => $parameters[0] ?? null]);
        }

        return parent::__call($method, $parameters); // TODO: Change the autogenerated stub
    }

    /**
     * Make element id.
     *
     * @return void
     */
    protected function makeId()
    {
        if ($this->id) {
            return;
        }
        $this->id = 'sparkline_'.$this->type.Str::random(8);
    }

    /**
     * Return JsonResponse instance.
     *
     * @param bool  $returnOptions
     * @param array $data
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function toJsonResponse(bool $returnOptions = true, array $data = [])
    {
        return response()->json(array_merge(
            [
                'status'  => 1,
                'values'  => $this->values(),
                'options' => $returnOptions ? $this->getOptions() : [],
            ],
            $data
        ));
    }
}
