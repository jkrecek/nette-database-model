<?php
namespace Krecek\Database\Annotation;

/**
 * Method called when creating new record.
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class OnUpdate
{
    /** @var string */
    public $methodName;
}