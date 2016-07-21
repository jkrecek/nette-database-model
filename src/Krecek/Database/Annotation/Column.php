<?php
namespace Krecek\Database\Annotation;

/**
 * Annotation for column name.
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Column
{
    /** @var string */
    public $name;
}