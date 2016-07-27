<?php

namespace Krecek\Database\Annotation;

use ReflectionClass;
use ReflectionMethod;

class MethodCall
{
    /** @var string */
    public $methodName;

    /**
     * @param object $object
     * @return mixed
     */
    public function call($object)
    {
        if (strpos($this->methodName, "::") !== false) {
            $reflectMethod = new ReflectionMethod($this->methodName);
            return $reflectMethod->invoke(null);
        } else {
            $reflect = new ReflectionClass($object);
            $reflectMethod = $reflect->getMethod($this->methodName);
            return $reflectMethod->invoke($this);
        }
    }
}