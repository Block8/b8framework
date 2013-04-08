<?php

namespace b8\Store\Base;
use b8\Exception\HttpException;

class AbstractBase
{	
	public function getWhere($where = array(), $limit = 25, $offset = 0, $joins = array(), $order = array(), $manualJoins = array(), $group = null, $manualWheres = array(), $whereType = 'AND')
	{	
		$query = 'SELECT ' . $this->tableName . '.* FROM ' . $this->tableName;
		$countQuery = 'SELECT COUNT(*) AS cnt FROM ' . $this->tableName;
		
		$wheres = array();
		$params = array();
		foreach($where as $key => $value)
		{
			$key = $this->fieldCheck($key);
			
			if(!is_array($value))
			{
				$params[] = $value;
				$wheres[] = $key . ' = ?';
			}
			else
			{
				if(isset($value['operator']) ) 
				{
					if(is_array($value['value']))
					{
						if($value['operator'] == 'between') 
						{
							$params[] = $value['value'][0];
							$params[] = $value['value'][1];
							$wheres[] = $key . ' BETWEEN ? AND ?';
						}
						elseif($value['operator'] == 'IN')
						{
							$in = array();
							
							foreach($value['value'] as $item)
							{
								$params[] = $item;
								$in[] = '?';
							}
							
							$wheres[] = $key . ' IN (' . implode(', ', $in) . ') ';
						}
						else
						{
							$ors = array();
							foreach($value['value'] as $item) {
								if($item == 'null') 
								{
									switch($value['operator'])
									{
										case '!=':
											$ors[] = $key . ' IS NOT NULL';
										break;

										case '==':
										default:
											$ors[] = $key . ' IS NULL';
										break;
									}
								}
								else
								{
									$params[] = $item;
									$ors[] = $this->fieldCheck($key) . ' ' . $value['operator'] . ' ?';
								}
							}
							$wheres[] = '(' . implode(' OR ', $ors) . ')';
						}
					} 
					else
					{
						if($value['operator'] == 'like')
						{
							$params[] = '%' . $value['value'] . '%';
							$wheres[] = $key . ' ' . $value['operator'] . ' ?';
						}
						else
						{
							if($value['value'] === 'null') 
							{
								switch($value['operator'])
								{
									case '!=':
										$wheres[] = $key . ' IS NOT NULL';
									break;

									case '==':
									default:
										$wheres[] = $key . ' IS NULL';
									break;
								}
							}
							else
							{
								$params[] = $value['value'];
								$wheres[] = $key . ' ' . $value['operator'] . ' ?';
							}
						}
					}
				} 
				else 
				{
					$wheres[] = $key . ' IN (\''.implode('\', \'', array_map('mysql_real_escape_string', $value)).'\')';
				}
			}
		}
		
		if(count($joins))
		{
			foreach($joins as $table => $join)
			{
				$query .= ' LEFT JOIN ' . $table . ' ' . $join['alias'] . ' ON ' . $join['on'] . ' ';
				$countQuery .= ' LEFT JOIN ' . $table . ' ' . $join['alias'] . ' ON ' . $join['on'] . ' ';
			}
		}
		
		if(count($manualJoins))
		{
			foreach($manualJoins as $join)
			{
				$query .= ' ' . $join . ' ';
				$countQuery .= ' ' . $join . ' ';
			}
		}
		
		$hasWhere = false;
		if(count($wheres))
		{
			$hasWhere = true;
			$query .= ' WHERE (' . implode(' '.$whereType.' ', $wheres) . ')';
			$countQuery .= ' WHERE (' . implode(' '.$whereType.' ', $wheres) . ')';
		}

		if(count($manualWheres))
		{
			foreach($manualWheres as $where)
			{
				if(!$hasWhere)
				{
					$hasWhere = true;
					$query .= ' WHERE ';
					$countQuery .= ' WHERE ';
				}
				else
				{
					$query .= ' ' . $where['type'] . ' ';
					$countQuery .= ' ' . $where['type'] . ' ';
				}

				$query .= ' ' . $where['query'];
				$countQuery .= ' ' . $where['query'];
				foreach($where['params'] as $param)
				{
					$params[] = $param;
				}
			}
		}
		
		if(!is_null($group))
		{
			$query .= ' GROUP BY ' . $group . ' ';
		}
		
		if(count($order))
		{
			$orders = array();
			if(is_string($order) && $order == 'rand') 
			{
				$query .= ' ORDER BY RAND() ';
			} 
			else 
			{
				foreach($order as $key => $value)
				{
					$orders[] = $this->fieldCheck($key) . ' ' . $value;
				}
				
				$query .= ' ORDER BY ' . implode(', ', $orders);
			}
		}
		
		if($limit)
		{
			$query .= ' LIMIT ' . $limit;
		}
		
		if($offset)
		{
			$query .= ' OFFSET ' . $offset;
		}

		$stmt = \b8\Database::getConnection('read')->prepare($countQuery);
		if($stmt->execute($params))
		{
			$res = $stmt->fetch(\PDO::FETCH_ASSOC);
			$count = (int)$res['cnt'];
		}
		else
		{
			$count = 0;
		}
		
		$stmt = \b8\Database::getConnection('read')->prepare($query);

		if($stmt->execute($params))
		{
			$res = $stmt->fetchAll(\PDO::FETCH_ASSOC);
			$rtn = array();
			
			foreach($res as $data)
			{
				$rtn[] = new $this->modelName($data);
			}
			
			return array('items' => $rtn, 'count' => $count);
		}
		else
		{
			return array('items' => array(), 'count' => 0);
		}
	}
	
	public function save(\b8\Model\Base\AbstractBase $obj, $saveAllColumns = false)
	{		
	    if(!isset($this->primaryKeyColumn))
	    {
			throw new HttpException\BadRequestException('Save not implemented for this store.');
	    }
	    
	    if(!($obj instanceof $this->modelName))
	    {
			throw new HttpException\BadRequestException(get_class($obj) . ' is an invalid model type for this store.');
	    }
	    
	    $data = $obj->getDataArray();
	    $modified = ($saveAllColumns) ? array_keys($data) : $obj->getModified();
		
				
	    if(isset($data[$this->primaryKeyColumn]))
	    {
			$updates = array();
			$update_params = array();
			foreach($modified as $key)
			{
				$updates[] = $key . ' = :' . $key;
				$update_params[] = array($key, $data[$key]);
			}
			
			if(count($updates))
			{
				$qs = 'UPDATE ' . $this->tableName . '
											SET ' . implode(', ', $updates) . ' 
											WHERE ' . $this->primaryKeyColumn . ' = :primaryKey';
				$q = \b8\Database::getConnection('write')->prepare($qs);
				
				foreach($update_params as $update_param)
				{
					$q->bindValue(':' . $update_param[0], $update_param[1]);
				}
				
				$q->bindValue(':primaryKey', $data[$this->primaryKeyColumn]);
				$q->execute();
								
				$rtn = $this->getByPrimaryKey($data[$this->primaryKeyColumn], 'write');

				return $rtn;
			}
			else 
			{
				return $obj;
			}
	    }
	    else
	    {
			$cols = array();
			$values = array();
			$qParams = array();
			foreach($modified as $key)
			{				
				$cols[] = $key;
				$values[] = ':' . $key;
				$qParams[':' . $key] = $data[$key];
			}
		
			if(count($cols))
			{
				$qs = 'INSERT INTO ' . $this->tableName . ' (' . implode(', ', $cols) . ') VALUES (' . implode(', ', $values) . ')';
				$q = \b8\Database::getConnection('write')->prepare($qs);

				if($q->execute($qParams))
				{
					return $this->getByPrimaryKey(\b8\Database::getConnection('write')->lastInsertId(), 'write');
				}
			}
	    }
	}
	
	public function delete(\b8\Model\Base\AbstractBase $obj)
	{
	    if(!isset($this->primaryKeyColumn))
	    {
			throw new HttpException\BadRequestException('Delete not implemented for this store.');
	    }
	    
	    if(!($obj instanceof $this->modelName))
	    {
			throw new HttpException\BadRequestException(get_class($obj) . ' is an invalid model type for this store.');
	    }
		
		$data = $obj->getDataArray();
		
		$q = \b8\Database::getConnection('write')->prepare('DELETE FROM ' . $this->tableName . ' WHERE ' . $this->primaryKeyColumn . ' = :primaryKey');
		$q->bindValue(':primaryKey', $data[$this->primaryKeyColumn]);
		$q->execute();

		return true;
	}
        
	/**
         * 
         */
	protected function fieldCheck($field)
	{
		if(is_null($field))
		{
			throw new HttpException\GeneralException('You cannot have null field');
		}
		
		if(strpos($field, '.') === false) 
		{
			return $this->tableName . '.' . $field;
		}
		
		return $field;
    }
}	