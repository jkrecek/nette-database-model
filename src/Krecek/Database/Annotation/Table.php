<?php
namespace Krecek\Database\Annotation;

/**
 * Annotation for table name.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class Table
{
    /** @var string */
    public $name;
}