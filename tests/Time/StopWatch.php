<?php 

namespace MintyPHP\Mocking\Tests\Time;

class StopWatch 
{ 
    private int $startTime;

    public function __construct()
    {
        $this->startTime = 0;
    }

    public function start():void 
    { 
        $this->startTime = intval(round(microtime(true)*1000));
    }

    public function stop():float 
    { 
        $endTime = intval(round(microtime(true)*1000));
        $timeSpent = $endTime - $this->startTime;
        throw new \Exception("This method should be mocked!");
        return $timeSpent;
    }
}