<?php
namespace b8\Database\Query;

class Criteria
{
    const TYPE_AND = 2;
    const TYPE_OR = 4;

    protected $type = self::TYPE_AND;
    protected $where;

    /**
     * @var Criteria[]
     */
    protected $children = [];

    public function __construct()
    {

    }

    public function where($where)
    {
        if (count($this->children)) {
            throw new \Exception('Cannot set where value when child criteria have been added.');
        }

        $this->where = $where;
        return $this;
    }

    public function add(Criteria $criteria)
    {
        if (!empty($this->where)) {
            throw new \Exception('Cannot add child criteria when where value is set.');
        }

        foreach (func_get_args() as $criteria) {
            $this->children[] = $criteria;

        }

        return $this;
    }

    public function setType($type = self::TYPE_AND)
    {
        $this->type = $type;
        return $this;
    }

    public function __toString()
    {
        $rtn = '';

        if ($this->where) {
            $rtn .= $this->where;
        } elseif (count($this->children)) {
            $type = ($this->type == self::TYPE_AND) ? ' AND ' : ' OR ';
            $rtn .= '(' . implode($type, $this->children) . ')';
        }

        return $rtn;
    }
}
