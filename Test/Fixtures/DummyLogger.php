<?php
namespace WebStream\ClassLoader\Test\Fixtures;

class DummyLogger
{
    public function debug($message)
    {
        var_dump($message);
        echo $message;
    }
}
