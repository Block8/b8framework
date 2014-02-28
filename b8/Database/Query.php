<?php
namespace b8\Database;

use b8\Database;
use b8\Database\Query\Criteria;

class Query
{
    /**
     * @var \b8\Database
     */
    protected $db;

    /**
     * Table name
     * @var string[]
     */
    protected $table;

    /**
     * Select
     * @var string
     */
    protected $select = '*';

    /**
     * Joins
     * @var array
     */
    protected $joins = [];

    /**
     * @var \b8\Database\Query\Criteria
     */
    protected $where;

    /**
     * @var string
     */
    protected $returnType;

    /**
     * @param string $returnType Model class name or array
     */
    public function __construct($returnType = 'array')
    {
        //$this->db = Database::getConnection('read');
        $this->returnType = $returnType;

        if ($returnType != 'array' && !class_exists($returnType)) {
            throw new \Exception('Invalid return type.');
        }
    }

    public function execute()
    {
        $query = 'SELECT ';
        $query .= $this->select;
        $query .= ' FROM ' . $this->table[0];

        if (!empty($table[1])) {
            $query .= ' ' . $table[1];
        }

        if (isset($this->where)) {
            $query .= ' WHERE ' . $this->where;
        }

        var_dump($query);
    }

    public function setSelect($select)
    {
        $this->select = $select;
        return $this;
    }

    public function setTable($name, $alias = null)
    {
        $this->table = [$name, $alias];
        return $this;
    }

    public function addJoin($table, $alias, $on)
    {
        $this->joins[] = ['table' => $table, 'alias' => $alias, 'on' => $on];
        return $this;
    }

    public function setWhere(Criteria $where)
    {
        $this->where = $where;
        return $this;
    }
}