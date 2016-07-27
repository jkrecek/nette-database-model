<?php
namespace Krecek\Database\Annotation;

/**
 * Annotation for form name.
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class FormControl
{
    /** @var string */
    public $controlName;
}