<?php

namespace App\Helpers;

class Cantor
{
    /**
     * Calculate a unique integer based on two integers (cantor pairing).
     */
    public static function calculate($x, $y)
    {
        return (($x + $y) * ($x + $y + 1)) / 2 + $y;
    }

    /**
     * Return the source integers from a cantor pair integer.
     */
    public static function reverse($z)
    {
        $t = floor((-1 + sqrt(1 + 8 * $z)) / 2);
        $x = $t * ($t + 3) / 2 - $z;
        $y = $z - $t * ($t + 1) / 2;
        return [$x, $y];
    }
}
