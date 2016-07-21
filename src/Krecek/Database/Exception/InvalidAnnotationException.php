<?php
namespace Krecek\Database\Exception;

use Exception;

/**
 * Class InvalidAnnotationException
 * @package Krecek\Database
 */
class InvalidAnnotationException extends Exception
{
    /**
     * @param object|null $annotation
     * @param string $className
     * @throws InvalidAnnotationException
     */
    public static function assert($annotation, $className)
    {
        if ($annotation === null) {
            throw new self("Invalid annotation: Annotation is null.");
        }
        if (!($annotation instanceof $className)) {
            throw new self("Invalid annotation: Annotation is not instance of {$className}.");
        }
    }
}