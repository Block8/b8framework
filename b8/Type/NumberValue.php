<?php

namespace b8\Type;

class NumberValue extends ScalarValue
{
    public function round($decimals)
    {
        if (is_numeric($this->value)) {
            return round($this->value, $decimals);
        }

        return null;
    }

    public function toCurrency()
    {
        if (is_numeric($this->value)) {
            return number_format($this->value, 2);
        }

        return null;
    }
}