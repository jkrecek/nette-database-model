<?php
namespace Krecek\Database;


use Doctrine\Common\Annotations\Reader;
use Krecek\Database\Exception\InvalidClassException;
use ReflectionClass;

abstract class StoredObject implements IDependencyProvider
{
    /**
     * @internal
     * @var Reader
     */
    protected $annotationReader;

    /**
     * @internal
     * @var IDatabaseLink
     */
    protected $databaseLink;

    /************** internal methods **************/

    /**
     * StoredEntity constructor.
     * @param IDependencyProvider $provider
     */
    private function __construct(IDependencyProvider $provider)
    {
        $this->annotationReader = $provider->provideAnnotationReader();
        $this->databaseLink = $provider->provideDatabaseLink();
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

    /************** static methods **************/


    /**
     * Returns whether $class is child of $parentClass
     * @param $class
     * @param $parentClass
     * @return bool
     */
    private static function isChildOf($class, $parentClass)
    {
        $classRef = new ReflectionClass($class);
        return $classRef->isSubclassOf($parentClass);
    }


    /**
     * @internal
     * @param IDependencyProvider $provider
     * @return self
     */
    public static function newInstance(IDependencyProvider $provider)
    {
        return new static($provider);
    }

    /**
     * Checks whether $class is child of $parentClass and throws exception otherwise.
     * @param $class
     * @param $parentClass
     * @throws InvalidClassException
     */
    protected static function mustBeChildOf($class, $parentClass)
    {
        $isChild = self::isChildOf($class, $parentClass);
        if (!$isChild) {
            throw new InvalidClassException(get_called_class(), $class, $parentClass);
        }
    }


}