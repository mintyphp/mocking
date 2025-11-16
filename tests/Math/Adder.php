<?php 

namespace MintyPHP\Mocking\Tests\Math;

use Exception;

class Adder 
{ 
    public static function add($a, $b):int 
    { 
        throw new Exception("This method should be mocked!");
        return $a+$b; 
    } 
}