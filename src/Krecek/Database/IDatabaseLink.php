<?php
namespace Krecek\Database;

use Nette\Database\IStructure;
use Nette\Database\ResultSet;
use Nette\Database\Table\Selection;

interface IDatabaseLink
{

    /**
     * Returns Selection for desired table name
     * @param string $tableName
     * @return Selection
     */
    function getTable($tableName);

    /**
     * Returns database structure
     * @return IStructure
     */
    function getStructure();

    /**
     * Generates and executes SQL query.
     * @param  string
     * @param  mixed [parameters, ...]
     * @return ResultSet
     */
    function query($sql);

}