<?php
namespace Krecek\Database\Annotation;

use Doctrine\Common\Annotations\AnnotationReader;
use Krecek\Database\Exception\InvalidAnnotationException;
use ReflectionClass;

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

    /**
     * @param AnnotationReader $annotationReader
     * @return string
     * @throws InvalidAnnotationException
     */
    public function getTable(AnnotationReader $annotationReader)
    {
        $reflect = $this->getClassReflection();
        $annotation = $annotationReader->getClassAnnotation($reflect, Table::class);
        InvalidAnnotationException::assert($annotation, Table::class);
        return $annotation->name;
    }

    /**
     * @return ReflectionClass
     */
    private function getClassReflection()
    {
        return new ReflectionClass($this->className);
    }
}