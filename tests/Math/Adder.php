<?php 

namespace MintyPHP\Mocking\Tests\Math;

use Exception;

class Adder 
{ 
    public static function add($a, $b):int 
    { 
        $c = $a + $b;
        throw new Exception("This method should be mocked!");
        return $c; 
    }
}