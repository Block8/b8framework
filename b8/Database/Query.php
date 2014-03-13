<?php
namespace b8\Database;

use PDOStatement;
use b8\Database;
use b8\Database\Query\Criteria;

class Query
{
    /**
     * @var \b8\Database
     */
    protected $database;

    /**
     * Table name
     * @var string[]
     */
    protected $table = [];

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
     * @var array
     */
    protected $params = [];

    /**
     * @var string
     */
    protected $returnType;

    /**
     * @var PDOStatement
     */
    protected $stmt;

    /**
     * @var string[]
     */
    protected $order = [];

    /**
     * @var int
     */
    protected $limit;

    /**
     * @var int
     */
    protected $offset;

    /**
     * @param string $returnType Model class name or array
     * @param string $connectionType read or write
     * @throws
     */
    public function __construct($returnType = 'array', $connectionType = 'read')
    {
        $this->database = Database::getConnection($connectionType);
        $this->returnType = $returnType;

        if ($returnType != 'array' && !class_exists($returnType)) {
            throw new \Exception('Invalid return type.');
        }
    }

    public function execute()
    {
        $this->stmt = $this->database->prepare($this->buildFullQuery());

        foreach ($this->params as $key => $value) {
            $this->stmt->bindValue($key, $value);
        }

        $this->stmt->execute();

        return $this;
    }

    public function getSql()
    {
        return $this->buildFullQuery();
    }

    protected function buildFullQuery()
    {
        $query = $this->buildBaseQuery();

        if (count($this->order)) {
            $order = [];

            foreach ($this->order as $item) {
                $order[] = '`' . $item[0] . '` ' . $item[1];
            }

            $query .= ' ORDER BY ' . implode(', ', $order) . ' ';
        }

        if (!is_null($this->limit)) {
            $query .= ' LIMIT ' . $this->limit . ' ';
        }

        if (!is_null($this->offset)) {
            $query .= ' OFFSET ' . $this->offset . ' ';
        }

        return $query;
    }

    protected function buildBaseQuery()
    {
        $query = 'SELECT ';
        $query .= $this->select;
        $query .= ' FROM `' . $this->table[0] . '` ';

        if (isset($this->table[1])) {
            $query .= ' ' . $this->table[1] . ' ';
        }

        if (!empty($table[1])) {
            $query .= ' ' . $table[1];
        }

        foreach ($this->joins as $join) {
            $query .= ' '.$join['type'].' JOIN `' . $join['table'] . '` ' . $join['alias'] . ' ON ' . $join['on'] . ' ';
        }

        if (isset($this->where)) {
            $query .= ' WHERE ' . $this->where;
        }

        return $query;
    }

    public function getCount()
    {
        $select = $this->select;
        $this->select = 'COUNT(*) AS total';
        $query = $this->buildBaseQuery();
        $this->select = $select;

        $stmt = $this->database->prepare($query);

        foreach ($this->params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        if ($stmt->execute()) {
            $res = $stmt->fetch(Database::FETCH_ASSOC);
            return (int)$res['total'];
        }

        return 0;
    }

    public function fetch()
    {
        $rtn = $this->stmt->fetch(Database::FETCH_ASSOC);

        if ($this->returnType != 'array') {
            $type = $this->returnType;
            $rtn = new $type($rtn);
        }

        return $rtn;
    }

    public function fetchAll()
    {
        $rtn = $this->stmt->fetchAll(Database::FETCH_ASSOC);

        foreach ($rtn as &$item) {
            if ($this->returnType != 'array') {
                $type = $this->returnType;
                $item = new $type($item);
            }
        }

        return $rtn;
    }

    public function select($select)
    {
        $this->select = $select;
        return $this;
    }

    public function from($name, $alias = null)
    {
        $this->table = [$name, $alias];
        return $this;
    }

    public function join($table, $alias, $on, $type = 'LEFT')
    {
        $this->joins[] = ['table' => $table, 'alias' => $alias, 'on' => $on, 'type' => $type];
        return $this;
    }

    /**
     * @param string|Criteria $where
     * @return $this
     */
    public function where($where)
    {
        if (is_string($where)) {
            $this->where = new Criteria();
            $this->where->where($where);
        } elseif ($where instanceof Criteria) {
            $this->where = $where;
        }

        return $this;
    }

    public function order($column, $direction = 'DESC')
    {
        $this->order[] = [$column, $direction];
    }

    public function limit($limit)
    {
        $this->limit = (int)$limit;
    }

    public function offset($offset)
    {
        $this->offset = (int)$offset;
    }

    public function bind($key, $value)
    {
        $this->params[$key] = $value;
    }

    public function bindAll(array $values)
    {
        foreach ($values as $key => $value) {
            $this->bind($key, $value);
        }
    }
}
