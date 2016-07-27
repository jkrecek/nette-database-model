<?php
namespace Krecek\Database;

use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Krecek\Database\Annotation\Collection;
use Krecek\Database\Exception\InvalidAnnotationException;
use Nette\Database\Table\Selection;
use Nette\DI\Container;
use Nette\Object;
use ReflectionClass;

/**
 * Class StorageRepository
 * @package Krecek\Database
 */
abstract class StorageRepository extends Object implements IDependencyProvider
{
    /** @var IDatabaseLink */
    private $databaseLink;

    /** @var CachedReader */
    private $annotationReader;

    /**
     * StorageRepository constructor.
     * @param IDatabaseLink $databaseLink
     * @param Container $container
     */
    public function __construct(IDatabaseLink $databaseLink, Container $container)
    {
        $this->databaseLink = $databaseLink;
        $this->annotationReader = $container->getService('annotations.reader');
    }

    /************** private methods **************/


    /**
     * @internal
     * @return Selection
     */
    protected function getTable()
    {
        $table = $this->getTableName();
        return $this->databaseLink->getTable($table);
    }

    /************** interface IStorageDependencyProvider **************/

    /**
     * @internal
     * @return Reader
     */
    function provideAnnotationReader()
    {
        return $this->annotationReader;
    }

    /**
     * @internal
     * @return IDatabaseLink
     */
    function provideDatabaseLink()
    {
        return $this->databaseLink;
    }


    /************** annotation methods **************/

    /**
     * @internal
     * @param Reader $annotationReader
     * @return string
     * @throws InvalidAnnotationException
     */
    static function getCollectionClassName(Reader $annotationReader)
    {
        $reflect = new ReflectionClass(get_called_class());
        $annotation = $annotationReader->getClassAnnotation($reflect, Collection::class);
        InvalidAnnotationException::assert($annotation, Collection::class);
        return $annotation->className;
    }

    /**
     * @internal
     * @return string
     * @throws InvalidAnnotationException
     */
    function getInstanceCollectionClassName()
    {
        return static::getCollectionClassName($this->annotationReader);
    }

    /**
     * @internal
     * @return string
     * @throws InvalidAnnotationException
     */
    function getEntityClassName()
    {
        $collectionClassName = $this->getInstanceCollectionClassName();
        return $collectionClassName::getEntityClassName($this->annotationReader);
    }

    /**
     * @internal
     * @return string
     * @throws InvalidAnnotationException
     */
    function getTableName()
    {
        $entityClassName = $this->getEntityClassName();
        return $entityClassName::getTableName($this->annotationReader);
    }

    /************** public methods **************/

    /**
     * Creates new entity.
     * @return StoredEntity
     */
    public function create()
    {
        $entityClassName = $this->getEntityClassName();
        return $entityClassName::create($this, null);
    }

    /**
     * Creates collection for entire repository.
     * @return StoredCollection
     */
    public function listAll()
    {
        $collectionClassName = $this->getInstanceCollectionClassName();
        return $collectionClassName::create($this, $this->getTable());
    }

    /**
     * Loads entity from storage.
     * @param mixed $key
     * @return StoredEntity|null
     */
    public function get($key)
    {
        if ($key == null) {
            return null;
        }

        $row = $this->getTable()->get($key);
        if ($row == null) {
            return null;
        }

        $entityClassName = $this->getEntityClassName();
        return $entityClassName::create($this, $row);

    }

    /**
     * Loads entity matching $condition.
     * @param array $condition
     * @return StoredEntity|null
     */
    public function findOne(array $condition)
    {
        $row = $this->getTable()->where($condition)->fetch();
        if ($row == null) {
            return null;
        }

        $entityClassName = $this->getEntityClassName();
        return $entityClassName::create($this, $row);
    }

    /**
     * Loads collection from database matching $condition.
     * @param array $condition
     * @return StoredCollection
     */
    public function findMany(array $condition)
    {
        $selection = $this->getTable()->where($condition);
        $collectionClassName = $this->getInstanceCollectionClassName();
        return $collectionClassName::create($this, $selection);
    }

    /**
     * Deletes row by its primary value.
     * @param $key
     */
    public function delete($key)
    {
        $this->getTable()->wherePrimary($key)->delete();
    }
}