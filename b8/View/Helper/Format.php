<?php

namespace b8\View\Helper;

class Format
{
    public function __call($func, $args)
    {
        // Legacy
        if ($func == 'Currency') {
            return call_user_func_array(array($this, 'currency'), $args);
        }
    }

    public function currency($number, $symbol = true)
    {
        return ($symbol ? '£' : '') . number_format($number, 2, '.', ',');
    }
}
