<?php
namespace Krecek\Database\Exception;

use Exception;

/**
 * Class EntityException
 * @package Krecek\Database\Exception
 */
class EntityException extends Exception
{

    /**
     * StoredEntityException constructor.
     * @param string $message
     */
    public function __construct($message)
    {
        parent::__construct($message);
    }
}