<?php
namespace Krecek\Database\Annotation;

/**
 * Annotation linking Collection to StorageRepository.
 *
 * @Annotation
 * @Target({"CLASS"})
 */
class Collection
{
    /** @var string */
    public $className;
}