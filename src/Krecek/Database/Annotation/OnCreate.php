<?php
namespace Krecek\Database\Annotation;

/**
 * Method called when creating new record.
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class OnCreate
{
    /** @var string */
    public $methodName;
}