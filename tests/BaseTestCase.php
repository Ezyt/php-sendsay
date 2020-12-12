<?php

namespace Ezyt\Sendsay\Tests;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

date_default_timezone_set('UTC');

class BaseTestCase extends TestCase
{
    /**
     * Call protected class method using reflection
     *
     * @param string $obj
     * @param string $name
     * @param array $args
     * @return mixed
     * @throws ReflectionException
     */
    protected function callMethod(string $obj, string $name, array $args = [])
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method->invokeArgs(null, $args);
    }

    /**
     * @param string $class
     * @param string $name
     * @return mixed
     * @throws ReflectionException
     */
    protected function getProperty(string $class, string $name)
    {
        $property = new ReflectionProperty($class, $name);
        $property->setAccessible(true);
        return $property->getValue($class);
    }
}
