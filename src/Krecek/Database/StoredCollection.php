<?php
namespace Krecek\Database;

use Countable;
use Doctrine\Common\Annotations\Reader;
use Iterator;
use Krecek\Database\Annotation\Entity;
use Krecek\Database\Exception\InvalidAnnotationException;
use Krecek\Database\Exception\InvalidPropertyException;
use Nette\Database\Table\Selection;
use ReflectionClass;

/**
 * Class StoredCollection
 * @package Krecek\Database
 */
abstract class StoredCollection extends StoredObject implements Iterator, Countable
{
    /** @var Selection */
    protected $selection;

    /************** internal methods **************/

    /**
     * StoredCollection constructor.
     * @param Selection $selection
     */
    private function setSelection(Selection $selection)
    {
        $this->selection = $selection;
    }

    /************** annotation methods **************/

    /**
     * @internal
     * @param Reader $annotationReader
     * @return string
     * @throws InvalidAnnotationException
     */
    static function getEntityClassName(Reader $annotationReader)
    {
        $reflect = new ReflectionClass(get_called_class());
        $annotation = $annotationReader->getClassAnnotation($reflect, Entity::class);
        InvalidAnnotationException::assert($annotation, Entity::class);
        return $annotation->className;
    }


    /**
     * @internal
     * @return string
     * @throws InvalidAnnotationException
     */
    public function getInstanceEntityClassName()
    {
        return static::getEntityClassName($this->annotationReader);
    }

    public function getTableName()
    {
        $entityName = $this->getInstanceEntityClassName();
        return $entityName::getTableName($this->annotationReader);
    }

    /************** interface Iterator **************/

    /**
     * @return StoredEntity|null
     */
    public function current()
    {
        $row = $this->selection->current();
        $entityClassName = $this->getInstanceEntityClassName();
        return $entityClassName::create($this, $row);
    }

    public function next()
    {
        $this->selection->next();
    }

    public function key()
    {
        return $this->selection->key();
    }

    public function valid()
    {
        return $this->selection->valid();
    }

    public function rewind()
    {
        $this->selection->rewind();
    }

    /************** Public methods **************/

    /**
     * Sets limit clause.
     * @param int $limit
     * @param int $offset
     * @return self
     */
    public function limit($limit, $offset = null)
    {
        $this->selection->limit($limit, $offset);
        return $this;
    }

    /**
     * Adds order clause, more calls appends to the end.
     * @param string $columns
     * @return self
     */
    public function order($columns)
    {
        $this->selection->order($columns);
        return $this;
    }

    /**
     * Add order clause for property.
     * @param Sorter $sorter
     * @return StoredCollection
     * @throws InvalidPropertyException
     * @internal param $property
     * @internal param bool $asc
     */
    public function sort(Sorter $sorter)
    {
        return $this->order("{$sorter->getColumnNameForCollection($this)} {$sorter->getOrderType()}");
    }

    /**
     * Counts number of rows
     * @param string|null $column
     * @return int
     */
    public function count($column = null)
    {
        return $this->selection->count($column);
    }

    /**
     * Fetches all rows as associative array.
     * @param string|null $key
     * @param string|null $value
     * @return array
     */
    public function fetchPairs($key = null, $value = null)
    {
        return $this->selection->fetchPairs($key, $value);
    }

    /**
     * @param $condition
     * @return self
     */
    public function addCondition($condition)
    {
        $this->selection->where($condition);
        return $this;
    }

    /**
     * Deletes current collection.
     */
    public function delete()
    {
        $this->selection->delete();
    }

    /************** static methods **************/

    /**
     * @param IDependencyProvider $provider
     * @param Selection $selection
     * @return static
     */
    public static function create(IDependencyProvider $provider, $selection)
    {
        /** @var $collection StoredCollection */
        $collection = parent::newInstance($provider);
        if ($selection !== false && $selection !== null && $selection instanceof Selection) {
            $collection->setSelection($selection);
        }

        return $collection;
    }
}