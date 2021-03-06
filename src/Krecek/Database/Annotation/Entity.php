<?php
namespace Krecek\Database\Annotation;

/**
 * Annotation linking Entity to StorageRepository.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class Entity
{
    /** @var string */
    public $className;
}