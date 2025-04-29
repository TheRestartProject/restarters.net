<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use Spatie\Html\Facades\Html;

class Form extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'form';
    }

    /**
     * Generate a hidden field
     *
     * @param string $name
     * @param string $value
     * @param array $options
     * @return \Illuminate\Support\HtmlString
     */
    public static function hidden($name, $value = null, $options = [])
    {
        return new \Illuminate\Support\HtmlString(Html::hidden($name, $value)->attributes($options)->toHtml());
    }
    
    /**
     * Generate a text field
     *
     * @param string $name
     * @param string $value
     * @param array $options
     * @return \Illuminate\Support\HtmlString
     */
    public static function text($name, $value = null, $options = [])
    {
        return new \Illuminate\Support\HtmlString(Html::text($name, $value)->attributes($options)->toHtml());
    }
    
    /**
     * Generate a select dropdown
     *
     * @param string $name
     * @param array $options
     * @param string|array $selected
     * @param array $selectAttributes
     * @return \Illuminate\Support\HtmlString
     */
    public static function select($name, $options = [], $selected = null, $selectAttributes = [])
    {
        return new \Illuminate\Support\HtmlString(Html::select($name, $options, $selected)->attributes($selectAttributes)->toHtml());
    }
} 