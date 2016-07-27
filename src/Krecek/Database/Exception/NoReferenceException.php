<?php
namespace Krecek\Database\Exception;


use Exception;

class NoReferenceException extends Exception
{
    private $column;

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