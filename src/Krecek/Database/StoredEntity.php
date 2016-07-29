<?php
namespace Krecek\Database;

use ArrayAccess;
use Doctrine\Common\Annotations\Reader;
use Exception;
use Krecek\Database\Annotation\Column;
use Krecek\Database\Annotation\DefaultValue;
use Krecek\Database\Annotation\Exported;
use Krecek\Database\Annotation\FormControl;
use Krecek\Database\Annotation\MethodCall;
use Krecek\Database\Annotation\OnCreate;
use Krecek\Database\Annotation\OnUpdate;
use Krecek\Database\Annotation\Primary;
use Krecek\Database\Annotation\Reference;
use Krecek\Database\Annotation\Table;
use Krecek\Database\Exception\IncompleteEntityException;
use Krecek\Database\Exception\InvalidAnnotationException;
use Krecek\Database\Exception\NoReferenceException;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Forms\Controls\ChoiceControl;
use Nette\Forms\Form;
use Nette\Utils\ArrayHash;
use ReflectionClass;
use ReflectionObject;
use ReflectionProperty;

/**
 * Class StoredEntity
 * @package Krecek\Database
 */
abstract class StoredEntity extends StoredObject
{
    /** @var ActiveRow|NULL */
    protected $row = null;

    /** @var ArrayHash */
    private $cachedReferences;

    /************** internal methods **************/

    /**
     * Sets ActiveRow record for the entity.
     * @param ActiveRow $row
     */
    private function setRecord(ActiveRow $row)
    {
        $this->loadFromRecord($row);
    }

    /**
     * @internal
     * @param ActiveRow $row
     * @throws Exception
     */
    private function loadFromRecord(ActiveRow $row)
    {
        $this->row = $row;

        foreach ($row as $columnName => $value) {
            $property = $this->getPropertyByColumnName($columnName);
            if ($property) {
                $property->setAccessible(true);
                $property->setValue($this, $value);
            }

        }
    }

    /**
     * @internal
     * @return ReflectionProperty|null
     */
    private function getPrimaryColumn()
    {
        $reflect = new ReflectionClass($this);
        foreach ($reflect->getProperties() as $property) {
            $annotation = $this->annotationReader->getPropertyAnnotation($property, Primary::class);
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
        $columns = $this->databaseLink->getStructure()->getColumns($this->getInstanceTableName());

        $entityDbData = [];
        foreach ($columns as $columnData) {
            $columnName = $columnData['name'];
            $columnProperty = $this->getPropertyByColumnName($columnName);
            if ($columnProperty) {
                $columnValue = $this->getPropertySaveValue($columnProperty);
                if ($columnValue === null) {
                    if ($columnData['autoincrement'] == true) {
                        continue;
                    }

                    if ($columnData['nullable'] == false) {
                        throw new IncompleteEntityException($columnProperty->getName());
                    }
                }


                $entityDbData[$columnName] = $columnValue;
            }
        }

        return $entityDbData;
    }


    /**
     * @internal
     * @param ReflectionProperty $reflectionProperty
     * @return string
     */
    private function getColumnNameForProperty(ReflectionProperty $reflectionProperty)
    {
        return self::getClassColumnNameForProperty($this->annotationReader, $reflectionProperty);
    }

    /**
     * @internal
     * @param ReflectionProperty $reflectionProperty
     * @return string|null
     */
    private function getFormControlNameForProperty(ReflectionProperty $reflectionProperty)
    {
        $annotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, FormControl::class);
        if ($annotation !== null && $annotation instanceof FormControl) {
            return $annotation->controlName;
        }

        return null;
    }

    /**
     * @internal
     * @param ReflectionProperty $reflectionProperty
     * @return string|null
     */
    private function getReferenceClassForProperty(ReflectionProperty $reflectionProperty)
    {
        $annotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, Reference::class);
        if ($annotation !== null && $annotation instanceof Reference) {
            return $annotation->className;
        }

        return null;
    }

    /**
     * @internal
     * @param ReflectionProperty $reflectionProperty
     * @return string|null
     */
    private function getDefaultValueForProperty(ReflectionProperty $reflectionProperty)
    {
        $annotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, DefaultValue::class);
        if ($annotation !== null && $annotation instanceof DefaultValue) {
            return $annotation->value;
        }

        return null;
    }

    /**
     * @internal
     * @param string $searchColumnName
     * @return ReflectionProperty|null
     */
    private function getPropertyByColumnName($searchColumnName)
    {
        return self::getClassPropertyByColumnName($this->annotationReader, $searchColumnName);
    }

    public static function getClassPropertyByColumnName(Reader $annotationReader, $searchColumnName)
    {
        $reflectObject = new ReflectionClass(get_called_class());

        foreach ($reflectObject->getProperties() as $property) {
            $columnName = self::getClassColumnNameForProperty($annotationReader, $property);
            if ($columnName == $searchColumnName) {
                return $property;
            }
        }

        return null;
    }

    /**
     * @internal
     * @param string $searchFormControlName
     * @return ReflectionProperty|null
     */
    private function getPropertyByFormControlName($searchFormControlName)
    {
        $reflectObject = new ReflectionObject($this);

        foreach ($reflectObject->getProperties() as $property) {
            $formControlName = $this->getFormControlNameForProperty($property);
            if ($formControlName !== null && $formControlName == $searchFormControlName) {
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
            $relatedAnnotationClass = OnCreate::class;
        } else {
            $relatedAnnotationClass = OnUpdate::class;
        }

        $annotation = $this->annotationReader->getPropertyAnnotation($reflectionProperty, $relatedAnnotationClass);
        if ($annotation != null && $annotation instanceof MethodCall) {
            return $annotation->call($this);
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


        $changedValues = [];


        foreach ($this->row as $columnName => $value) {
            $property = $this->getPropertyByColumnName($columnName);
            if ($property) {
                $propertyValue = $this->getPropertySaveValue($property);
                if ($propertyValue != $value) {
                    $changedValues[$columnName] = $propertyValue;
                }
            }

        }

        return $changedValues;
    }

    /************** annotation methods **************/

    /**
     * @internal
     * @param Reader $annotationReader
     * @return string
     * @throws InvalidAnnotationException
     */
    static function getTableName(Reader $annotationReader)
    {
        $reflect = new ReflectionClass(get_called_class());
        $annotation = $annotationReader->getClassAnnotation($reflect, Table::class);
        InvalidAnnotationException::assert($annotation, Table::class);
        return $annotation->name;
    }

    /**
     * @internal
     * @return string
     * @throws InvalidAnnotationException
     */
    private function getInstanceTableName()
    {
        return static::getTableName($this->annotationReader);
    }

    /************** interface IStorageDependencyProvider **************/

    /** @inheritDoc */
    function provideAnnotationReader()
    {
        return $this->annotationReader;
    }

    /** @inheritDoc */
    function provideDatabaseLink()
    {
        return $this->databaseLink;
    }

    /************** public methods **************/

    /**
     * Returns primary value of entity.
     * @return mixed|null
     */
    public function getPrimary()
    {
        $primaryProperty = $this->getPrimaryColumn();
        if ($primaryProperty === null) {
            return null;
        }

        $primaryProperty->setAccessible(true);
        return $primaryProperty->getValue($this);
    }

    /**
     * Returns selection for entity table.
     * @return Selection
     */
    public function getTable()
    {
        return $this->databaseLink->getTable($this->getInstanceTableName());
    }

    /**
     * Returns whether entity is new or loaded.
     * @return bool
     */
    public function isNewEntity()
    {
        return $this->row === null;
    }

    /**
     * Saves entity into database.
     * @return void
     */
    public function save()
    {
        if ($this->isNewEntity()) {
            $insertValues = $this->getInsertValues();
            $row = $this->getTable()->insert($insertValues);
            $this->loadFromRecord($row);
        } else {
            $changedValues = $this->getChangedValues();
            $this->row->update($changedValues);
        }
    }

    /**
     * Deletes current record if saved.
     * @return void
     */
    public function delete()
    {
        if ($this->isNewEntity()) {
            return;
        } else {
            $this->row->delete();
        }
    }

    /**
     * Returns referencing rows.
     * @param string $collectionClass
     * @param string|null $throughColumn
     * @return StoredCollection|ArrayAccess
     */
    public function related($collectionClass, $throughColumn = null)
    {
        if ($this->isNewEntity()) {
            return null;
        } else {
            $entityClass = $collectionClass::getEntityClassName($this->annotationReader);
            $key = $entityClass::getTableName($this->annotationReader);
            return $collectionClass::create($this, $this->row->related($key, $throughColumn));
        }
    }

    /**
     * @param string $entityClass
     * @param string $key
     * @param null $column
     * @return StoredEntity|null
     * @throws NoReferenceException
     */
    public function reference($entityClass, $key = null, $column = null)
    {
        if ($this->cachedReferences == null) {
            $this->cachedReferences = new ArrayHash();
        }

        if ($this->isNewEntity()) {
            return null;
        }

        if ($key == null) {
            $columnToDiscoverReference = $entityClass;
            $reflectProperty = $this->getPropertyByColumnName($columnToDiscoverReference);
            if ($reflectProperty) {
                $entityClass = $this->getReferenceClassForProperty($reflectProperty);
                if (!$entityClass) {
                    throw new NoReferenceException($columnToDiscoverReference);
                }

                $column = $columnToDiscoverReference;
                $key = $entityClass::getTableName($this->annotationReader);
            }
        }

        if (!$this->cachedReferences->offsetExists($key)) {
            $reference = $entityClass::create($this, $this->row->getTable()->getReferencedTable($this->row, $key, $column));
            $this->cachedReferences->offsetSet($key, $reference);
        }

        return $this->cachedReferences->offsetGet($key);
    }

    /**
     * Sets form default values according to entity.
     * @param Form $form
     */
    public function setDefaultFormValues(Form $form)
    {
        foreach ($form->getControls() as $name => $control) {
            $property = $this->getPropertyByFormControlName($name);
            if ($property === null) {
                continue;
            }

            $property->setAccessible(true);
            $targetValue = $property->getValue($this);
            if ($control instanceof ChoiceControl) {
                if (!isset($control->getItems()[$targetValue])) {
                    $targetValue = null;
                }
            }

            if ($targetValue == null && $this->isNewEntity()) {
                $propertyDefault = $this->getDefaultValueForProperty($property);
                if ($propertyDefault) {
                    $targetValue = $propertyDefault;
                }
            }

            $control->setDefaultValue($targetValue);
        }
    }

    /**
     * Sets entity values by filled form values.
     * @param Form $form
     */
    public function setFilledValues(Form $form)
    {
        foreach ($form->getControls() as $name => $control) {
            $property = $this->getPropertyByFormControlName($name);
            if ($property === null) {
                continue;
            }

            $property->setAccessible(true);
            $property->setValue($this, $control->getValue());
        }
    }

    /**
     * Returns exported values in array.
     * @return ArrayHash
     */
    public function toArray()
    {
        $reflect = new ReflectionObject($this);

        $data = new ArrayHash();
        foreach ($reflect->getProperties() as $property) {
            $annotations = $this->annotationReader->getPropertyAnnotations($property);
            $shouldBeExported = false;
            foreach ($annotations as $annotation) {
                if ($annotation instanceof Column ||
                    $annotation instanceof Exported
                ) {
                    $shouldBeExported = true;
                    break;
                }
            }

            if ($shouldBeExported) {
                $property->setAccessible(true);
                $data[$property->getName()] = $property->getValue($this);
            }
        }

        return $data;
    }

    /************** static methods **************/

    /**
     * @param IDependencyProvider $provider
     * @param ActiveRow|boolean|null $row
     * @return static
     */
    public static function create(IDependencyProvider $provider, $row = null)
    {
        /** @var $entity StoredEntity */
        $entity = parent::newInstance($provider);
        if ($row !== false && $row !== null && $row instanceof ActiveRow) {
            $entity->setRecord($row);
        }

        return $entity;
    }

    /**
     * @param Reader $annotationReader
     * @param ReflectionProperty $reflectionProperty
     * @return string
     */
    public static function getClassColumnNameForProperty(Reader $annotationReader, ReflectionProperty $reflectionProperty)
    {
        $columnName = $reflectionProperty->getName();
        $annotation = $annotationReader->getPropertyAnnotation($reflectionProperty, Column::class);
        if ($annotation !== null && $annotation instanceof Column) {
            $columnName = $annotation->name;
        }

        return $columnName;
    }
}