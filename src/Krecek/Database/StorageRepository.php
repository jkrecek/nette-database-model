<?php
namespace Krecek\Database;

use Doctrine\Common\Annotations\CachedReader;
use Krecek\Database\Annotation\Entity;
use Krecek\Database\Exception\InvalidAnnotationException;
use Nette\Database\Table\Selection;
use Nette\DI\Container;
use Nette\Object;
use ReflectionClass;

/**
 * Class StorageRepository
 * @package Krecek\Database
 */
abstract class StorageRepository extends Object
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

    /************** internal methods **************/

    /**
     * @internal
     * @return string
     */
    private function getEntityClass()
    {
        return $this->getEntityClassEntityAnnotation()->className;
    }

    /**
     * @internal
     * @return Entity
     * @throws InvalidAnnotationException
     */
    private function getEntityClassEntityAnnotation()
    {
        $reflect = new ReflectionClass($this);
        $annotation = $this->annotationReader->getClassAnnotation($reflect, Entity::class);
        InvalidAnnotationException::assert($annotation, Entity::class);
        return $annotation;
    }

    /**
     * @internal
     * @return Selection
     */
    protected function getDBTable()
    {
        $table = $this->getEntityTable();
        return $this->databaseLink->getTable($table);
    }

    /**
     * @internal
     * @return string
     */
    private function getEntityTable()
    {
        $entityAnnotation = $this->getEntityClassEntityAnnotation();
        $tableName = $entityAnnotation->getTable($this->annotationReader);
        return $tableName;
    }

    /**
     * @internal
     * @param StoredEntity $entity
     */
    private function injectIntoEntity(StoredEntity $entity)
    {
        $entity->injectDependencies($this->annotationReader, $this->databaseLink);
    }

    /************** public methods **************/

    /**
     * Creates new entity
     * @return StoredEntity
     */
    public function create()
    {
        $class = $this->getEntityClass();

        /** @var $entity StoredEntity */
        $entity = new $class();
        $this->injectIntoEntity($entity);
        return $entity;
    }

    /**
     * Loads entity from storage
     * @param mixed $key
     * @return StoredEntity
     */
    public function get($key)
    {
        $class = $this->getEntityClass();
        $row = $this->getDBTable()->get($key);

        /** @var $entity StoredEntity */
        $entity = new $class();
        $this->injectIntoEntity($entity);
        $entity->setRecord($row);
        return $entity;
    }

    /**
     * Loads entity from database or creates new if record does not exist
     * @param mixed $key
     * @return StoredEntity
     */
    public function getOrCreate($key)
    {
        $entity = $this->get($key);
        if ($entity == null) {
            $entity = $this->create();
        }

        return $entity;
    }
}