<?php
namespace Krecek\Database;

use Doctrine\Common\Annotations\CachedReader;
use Exception;
use Krecek\Database\Annotation\Column;
use Krecek\Database\Annotation\OnCreate;
use Krecek\Database\Annotation\OnUpdate;
use Krecek\Database\Annotation\Table;
use Krecek\Database\Exception\InvalidAnnotationException;
use Nette\Database\Table\ActiveRow;
use Nette\Object;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;

/**
 * Class StoredEntity
 * @package Krecek\Database
 */
abstract class StoredEntity extends Object
{
    /** @var ActiveRow|NULL */
    private $row = null;

    /** @var CachedReader */
    private $annotationReader;

    /** @var IDatabaseLink */
    private $databaseLink;


    /************** internal methods **************/

    /**
     * @internal
     * @param ActiveRow $row
     * @throws Exception
     */
    private function loadFromRecord(ActiveRow $row)
    {
        $this->row = $row;

        $reflectObject = new ReflectionObject($this);

        foreach ($reflectObject->getProperties() as $property) {
            $columnName = $this->getColumnNameForProperty($property);

            if (isset($row->$columnName)) {
                $property->setAccessible(true);
                $property->setValue($this, $row->$columnName);
            }
        }

        $primaryProperty = $this->getPrimaryColumn();
        if (!$primaryProperty) {
            throw new Exception(); // TODO
        }

        $primaryProperty->setValue($this, $row->getPrimary());
    }

    /**
     * @internal
     * @param ReflectionProperty $reflectionProperty
     * @return string
     */
    private function getColumnNameForProperty(ReflectionProperty $reflectionProperty)
    {
        $columnName = $reflectionProperty->getName();
        $annotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, Column::class);
        if ($annotation !== null && $annotation instanceof Column) {
            $columnName = $annotation->name;
        }

        return $columnName;
    }

    /**
     * @internal
     * @return ReflectionProperty|null
     */
    private function getPrimaryColumn()
    {
        $reflect = new ReflectionClass($this);
        foreach ($reflect->getProperties() as $property) {
            $annotation = $this->annotationReader->getPropertyAnnotation($property, 'App\Model\Annotation\Primary');
            if ($annotation !== null) {
                return $property;
            }
        }

        return null;
    }

    /**
     * @internal
     * @return array
     * @throws IncompleteEntityException
     */
    private function getInsertValues()
    {
        $columns = $this->databaseLink->getStructure()->getColumns($this->getTableName());

        $entityDbData = [];
        foreach ($columns as $columnData) {
            $columnName = $columnData['name'];
            $columnProperty = $this->getPropertyByColumnName($columnName);
            $columnValue = $this->getPropertyValue($columnProperty);
            if ($columnValue === null) {
                if ($columnData['autoincrement'] == true) {
                    continue;
                }

                if ($columnData['nullable'] == false) {
                    throw new IncompleteEntityException($columnProperty->getName());
                }
            }


            $entityDbData[$columnName] = $this->getPropertySaveValue($columnProperty);
        }

        return $entityDbData;
    }

    /**
     * @internal
     * @return string
     * @throws InvalidAnnotationException
     */
    private function getTableName()
    {
        $reflect = new ReflectionClass($this);
        $annotation = $this->annotationReader->getClassAnnotation($reflect, Table::class);
        InvalidAnnotationException::assert($annotation, Table::class);
        return $annotation->name;
    }

    /**
     * @internal
     * @param string $searchColumnName
     * @return null|ReflectionProperty
     */
    private function getPropertyByColumnName($searchColumnName)
    {
        $reflectObject = new ReflectionObject($this);

        foreach ($reflectObject->getProperties() as $property) {
            $columnName = $this->getColumnNameForProperty($property);
            if ($columnName == $searchColumnName) {
                return $property;
            }
        }

        return null;
    }

    /**
     * @internal
     * @param ReflectionProperty $reflectionProperty
     * @return mixed
     */
    private function getPropertyValue(ReflectionProperty $reflectionProperty)
    {
        $reflectionProperty->setAccessible(true);
        $value = $reflectionProperty->getValue($this);
        return $value;
    }

    /**
     * @internal
     * @param ReflectionProperty $reflectionProperty
     * @return mixed
     */
    private function getPropertySaveValue(ReflectionProperty $reflectionProperty)
    {
        $object = $this->getPropertyValue($reflectionProperty);

        if ($this->row == null) {
            $annotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, OnCreate::class);
            if ($annotation != null && $annotation instanceof OnCreate) {
                return $this->callAnnotationMethod($annotation->methodName);
            }
        }

        if ($this->row !== null) {
            $annotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, OnUpdate::class);
            if ($annotation != null && $annotation instanceof OnUpdate) {
                return $this->callAnnotationMethod($annotation->methodName);
            }
        }

        return $object;
    }

    /**
     * @internal
     * @return array
     */
    private function getChangedValues()
    {
        if ($this->row === null) {
            return [];
        }

        $reflectObject = new ReflectionObject($this);

        $changedValues = [];

        foreach ($reflectObject->getProperties() as $property) {
            $columnName = $this->getColumnNameForProperty($property);

            if (isset($this->row->$columnName)) {
                $property->setAccessible(true);
                if ($this->row->$columnName != $property->getValue($this)) {
                    $changedValues[$columnName] = $property->getValue($this);
                }
            }
        }

        return $changedValues;
    }

    /**
     * @internal
     * @param string $methodName
     * @return mixed
     */
    private function callAnnotationMethod($methodName)
    {
        if (strpos($methodName, "::") !== false) {
            $reflectMethod = new ReflectionMethod($methodName);
            return $reflectMethod->invoke(null);
        } else {
            $reflect = new ReflectionClass($this);
            $reflectMethod = $reflect->getMethod($methodName);
            return $reflectMethod->invoke($this);
        }
    }

    /************** public methods **************/

    /**
     * Inject dependencies into entity
     * @param CachedReader $annotationReader
     * @param IDatabaseLink $databaseLink
     */
    public function injectDependencies(CachedReader $annotationReader, IDatabaseLink $databaseLink)
    {
        $this->annotationReader = $annotationReader;
        $this->databaseLink = $databaseLink;
    }


    /**
     * Sets ActiveRow record for the entity
     * @param ActiveRow $row
     */
    public function setRecord(ActiveRow $row)
    {
        $this->loadFromRecord($row);
    }

    /**
     * Returns whether entity is new or loaded
     * @return bool
     */
    public function isNewEntity()
    {
        return $this->row === null;
    }

    /**
     * Saves entity into database
     */
    public function save()
    {
        if ($this->isNewEntity()) {
            $insertValues = $this->getInsertValues();
            $table = $this->databaseLink->getTable($this->getTableName());
            $row = $table->insert($insertValues);
            $this->loadFromRecord($row);
        } else {
            $changedValues = $this->getChangedValues();
            $this->row->update($changedValues);
        }
    }

}