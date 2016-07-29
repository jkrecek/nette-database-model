<?php
namespace Krecek\Database\Annotation;

/**
 * Annotation specifing default value for property.
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class DefaultValue
{
    /** @var string */
    public $value;

}