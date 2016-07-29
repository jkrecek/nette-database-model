<?php
namespace Krecek\Database\Exception;


/**
 * Class NoReferenceException
 * @package Krecek\Database\Exception
 */
class NoReferenceException extends EntityException
{
    /** @var string */
    private $column;

    /**
     * NoReferenceException constructor.
     * @param string $column
     */
    public function __construct($column)
    {
        $this->column = $column;
        parent::__construct("Could not find reference for column `{$column}`.");
    }

    /**
     * @return string
     */
    public function getColumn()
    {
        return $this->column;
    }


}