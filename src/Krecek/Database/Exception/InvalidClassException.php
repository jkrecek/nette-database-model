<?php
namespace Krecek\Database\Exception;


/**
 * Class InvalidClassException
 * @package Krecek\Database\Exception
 */
class InvalidClassException extends EntityException
{
    /**
     * InvalidClassException constructor.
     * @param string $calledObject
     * @param string $class
     * @param string $requiredClass
     */
    public function __construct($calledObject, $class, $requiredClass)
    {
        parent::__construct("Call to object {$calledObject} requires child class of {$requiredClass}, got: {$class}");
    }
}