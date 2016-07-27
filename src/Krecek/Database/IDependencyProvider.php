<?php

namespace Krecek\Database;


use Doctrine\Common\Annotations\Reader;

interface IDependencyProvider
{

    /**
     * @return Reader
     */
    function provideAnnotationReader();

    /**
     * @return IDatabaseLink
     */
    function provideDatabaseLink();
}