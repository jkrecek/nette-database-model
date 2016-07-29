<?php
namespace Krecek\Database\Exception;


/**
 * Class InvalidAnnotationException
 * @package Krecek\Database
 */
class InvalidAnnotationException extends EntityException
{
    /**
     * @param object|null $annotation
     * @param string $className
     * @throws InvalidAnnotationException
     */
    public static function assert($annotation, $className)
    {
        if ($annotation === null) {
            throw new self("Invalid annotation: Annotation is null, should be instance of {$className}.");
        }
        if (!($annotation instanceof $className)) {
            throw new self("Invalid annotation: Annotation is not instance of {$className}.");
        }
    }
}