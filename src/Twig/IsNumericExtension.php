<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class IsNumericExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('is_numeric', [$this, 'isNumeric']),
        ];
    }

    public function isNumeric($value)
    {
        return is_numeric($value);
    }
}
