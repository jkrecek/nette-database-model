<?php
namespace Krecek\Database;


use Doctrine\Common\Annotations\Reader;
use Krecek\Database\Exception\InvalidPropertyException;
use Nette\Object;
use ReflectionException;
use ReflectionProperty;

class Sorter extends Object
{
    /** @var string */
    protected $propertyName;

    /** @var bool */
    protected $asc;

    /**
     * Sorter constructor.
     * @param $propertyName
     * @param $asc
     */
    public function __construct($propertyName, $asc)
    {
        $this->propertyName = $propertyName;
        $this->asc = $asc;
    }

    /**
     * @param Reader $annotationReader
     * @param $entityClass
     * @return string
     */
    private function getColumnName(Reader $annotationReader, $entityClass) {
        try {
            $reflectionProperty = new ReflectionProperty($entityClass, $this->propertyName);
            return $entityClass::getClassColumnNameForProperty($annotationReader, $reflectionProperty);
        } catch (ReflectionException $e) {
            return $this->propertyName;
        }
    }

    /**
     * @param StoredCollection $collection
     * @return string
     */
    public function getColumnNameForCollection(StoredCollection $collection) {
        return $this->getColumnName($collection->provideAnnotationReader(), $collection->getInstanceEntityClassName());
    }

    /**
     * @param StoredEntity $entity
     * @return string
     */
    public function getColumnNameForEntity(StoredEntity $entity) {
        return $this->getColumnName($entity->provideAnnotationReader(), get_class($entity));
    }

    /**
     * @return string
     */
    public function getOrderType() {
        return $this->asc ? "ASC" : "DESC";
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }

    /**
     * @return bool
     */
    public function getAsc()
    {
        return $this->asc;
    }

}