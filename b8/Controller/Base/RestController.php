<?php

namespace b8\Controller\Base;

class RestController extends \b8\Controller\Base\AbstractController
{
	const SEARCHTYPE_AND = 'AND';
	const SEARCHTYPE_OR  = 'OR';

	public $requiresAuthentication = true;
	public $updateLastAction = true;

	protected $activeUser = null;
	protected $where = array();
	protected $limit = null;
	protected $offset = null;
	protected $joins = array();
	protected $arrayDepth = 2;
	protected $params = null;
	protected $order = array();
	protected $group = null;
	protected $manualJoins = array();
	protected $manualWheres = array();
	protected $searchType = self::SEARCHTYPE_AND;

	public function init()
	{
	}

	public function setActiveUser(\b8\Type\RestUser $user)
	{
		$this->activeUser = $user;
	}

	public function getActiveUser()
	{
		return $this->activeUser;
	}

	public function index()
	{
		if(!$this->activeUser->checkPermission('canRead', $this->resourceName))
		{
			throw new HttpException\ForbiddenException('You do not have permission do this.');
		}

		$this->where      = $this->_parseWhere();
		$this->limit      = is_null($this->limit) ? $this->getParam('limit', 25) : $this->limit;
		$this->offset     = is_null($this->offset) ? $this->getParam('offset', 0) : $this->offset;
		$this->order      = is_null($this->order) || !count($this->order) ? $this->getParam('order', array()) : $this->order;
		$this->group      = is_null($this->group) || !count($this->group) ? $this->getParam('group', null) : $this->group;
		$this->searchType = $this->getParam('searchType', self::SEARCHTYPE_AND);

		$store = \b8\Store\Factory::getStore($this->modelName);
		$data  = $store->getWhere($this->where, $this->limit, $this->offset, $this->joins, $this->order, $this->manualJoins, $this->group, $this->manualWheres, $this->searchType);

		$rtn = array(
			'debug'  => array(
				'where'      => $this->where,
				'searchType' => $this->searchType,
			),
			'limit'  => $this->limit,
			'offset' => $this->offset,
			'total'  => $data['count'],
			'items'  => array()
		);

		foreach($data['items'] as $item)
		{
			$rtn['items'][] = $item->toArray($this->arrayDepth);
		}

		return $rtn;
	}

	/**
	 *
	 */
	protected function _parseWhere()
	{
		$clauses = array(
			'fuzzy'   => 'like',
			'gt'      => '>',
			'gte'     => '>=',
			'lt'      => '<',
			'lte'     => '<=',
			'neq'     => '!=',
			'between' => 'between'
		);

		$where = $this->getParam('where', array());
		$where = array_merge($where, $this->where);

		if(count($where))
		{
			foreach($where as $field => &$value)
			{
				if(!is_array($value) || !isset($value['operator']))
				{
					if(is_array($value) && count($value) == 1)
					{
						$value = array_shift($value);
					}

					$value = array(
						'operator' => '=',
						'value'    => $value
					);
				}
			}

			foreach($clauses as $clause => $operator)
			{
				$fields = $this->getParam($clause, array());

				if(count($clause))
				{
					if(!is_array($fields))
					{
						$fields = array($fields);
					}
					foreach($fields as $field)
					{
						if(isset($where[$field]))
						{
							$where[$field]['operator'] = $operator;
							if($operator == 'like')
							{
								$where[$field]['value'] = str_replace(' ', '%', $where[$field]['value']);
							}
						}
					}
				}
			}
		}

		return $where;
	}

	/**
	 *
	 */
	public function doDeed($obj, $deed)
	{
		if(empty($obj))
		{
			throw new HttpException\NotFoundException('The ' . strtolower($this->modelName) . ' you are trying to access does not exist.');
		}

		if($deed && method_exists($this, $deed . 'Deed'))
		{
			$obj = $this->{$deed . 'Deed'}($obj);
		}

		return $obj;
	}

	public function get($key, $deed = null)
	{
		if(!$this->activeUser->checkPermission('canRead', $this->resourceName))
		{
			throw new HttpException\ForbiddenException('You do not have permission do this.');
		}

		$rtn = \b8\Store\Factory::getStore($this->modelName)->getByPrimaryKey($key);

		if(is_object($rtn) && method_exists($rtn, 'toArray'))
		{
			$rtn = $rtn->toArray($this->arrayDepth);
		}

		return array(strtolower($this->modelName) => $rtn);
	}

	public function put($key, $deed = null)
	{
		if(!$this->activeUser->checkPermission('canEdit', $this->resourceName))
		{
			throw new HttpException\ForbiddenException('You do not have permission do this.');
		}

		$store = \b8\Store\Factory::getStore($this->modelName);

		if($obj = $store->getByPrimaryKey($key))
		{
			$obj->setValues($this->getParams());
			$rtn = $store->save($obj);

			return array(strtolower($this->modelName) => $rtn->toArray($this->arrayDepth));
		}
		else
		{
			return null;
		}
	}

	public function post($deed = null)
	{
		if(!$this->activeUser->checkPermission('canCreate', $this->resourceName))
		{
			throw new HttpException\ForbiddenException('You do not have permission do this.');
		}

		$store = \b8\Store\Factory::getStore($this->modelName);

		$modelClass = $this->modelClass;
		$obj        = new $modelClass();
		$obj->setValues($this->getParams());
		$rtn = $store->save($obj);

		return array(strtolower($this->modelName) => $rtn->toArray($this->arrayDepth));
	}

	public function delete($key, $deed = null)
	{
		if(!$this->activeUser->checkPermission('canDelete', $this->resourceName))
		{
			throw new HttpException\ForbiddenException('You do not have permission do this.');
		}

		$store = \b8\Store\Factory::getStore($this->modelName);

		if($obj = $store->getByPrimaryKey($key))
		{
			if($store->delete($obj))
			{
				return array('deleted' => true);
			}
		}
		else
		{
			return null;
		}
	}
}