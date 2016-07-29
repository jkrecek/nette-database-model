<?php
namespace Krecek\Database\Exception;


/**
 * Class IncompleteEntityException
 * @package Krecek\Database
 */
class IncompleteEntityException extends EntityException
{
    /** @var string */
    private $propertyName;

    /**
     * IncompleteEntityException constructor.
     * @param string $propertyName
     */
    public function __construct($propertyName)
    {
        $this->propertyName = $propertyName;

        parent::__construct("Property {$this->propertyName} cannot be null.");
    }

    /**
     * @return string
     */
    public function getPropertyName()
    {
        return $this->propertyName;
    }
}