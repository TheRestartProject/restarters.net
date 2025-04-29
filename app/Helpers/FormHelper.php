<?php

namespace App\Helpers;

use Spatie\Html\Html;

class FormHelper
{
    /**
     * @var Html
     */
    protected $html;
    
    /**
     * FormHelper constructor.
     * 
     * @param Html $html
     */
    public function __construct(Html $html)
    {
        $this->html = $html;
    }
    
    /**
     * Generate a hidden field
     *
     * @param string $name
     * @param string $value
     * @param array $options
     * @return \Illuminate\Support\HtmlString
     */
    public function hidden($name, $value = null, $options = [])
    {
        return new \Illuminate\Support\HtmlString($this->html->hidden($name, $value)->attributes($options)->toHtml());
    }
    
    /**
     * Generate a text field
     *
     * @param string $name
     * @param string $value
     * @param array $options
     * @return \Illuminate\Support\HtmlString
     */
    public function text($name, $value = null, $options = [])
    {
        return new \Illuminate\Support\HtmlString($this->html->text($name, $value)->attributes($options)->toHtml());
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
    public function select($name, $options = [], $selected = null, $selectAttributes = [])
    {
        return new \Illuminate\Support\HtmlString($this->html->select($name, $options, $selected)->attributes($selectAttributes)->toHtml());
    }
} 