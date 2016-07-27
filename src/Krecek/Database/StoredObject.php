<?php
/**
 * Created by PhpStorm.
 * User: honzakrecek
 * Date: 22/07/16
 * Time: 11:54
 */

namespace Krecek\Database;


use Doctrine\Common\Annotations\Reader;

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
     * @internal
     * @param IDependencyProvider $provider
     * @return self
     */
    public static function newInstance(IDependencyProvider $provider)
    {
        return new static($provider);
    }

}