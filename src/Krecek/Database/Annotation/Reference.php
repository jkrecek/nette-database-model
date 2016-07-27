<?php

namespace Krecek\Database\Annotation;

/**
 * Annotation representing referencing class.
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Reference
{
    public $className;
}