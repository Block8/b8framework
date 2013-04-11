<?php

namespace b8\Database;

class Map
{
	protected $_db = null;
	protected $_tables = array();

	public function __construct(\b8\Database $db)
	{
		$this->_db = $db;
	}

	public function generate()
	{
		$tables = $this->_getTables();


		foreach($tables as $table)
		{
			$this->_tables[$table]              = array();
			$this->_tables[$table]['php_name']  = $this->_generatePhpName($table);
		}

		$this->_getRelationships();
		$this->_getColumns();

		return $this->_tables;
	}

	protected function _getTables()
	{
		$details = $this->_db->getDetails();

		$rtn = array();

		foreach($this->_db->query('SHOW TABLES')->fetchAll(\PDO::FETCH_ASSOC) as $tbl)
		{
			$rtn[] = $tbl['Tables_in_' . $details['db']];
		}

		return $rtn;
	}

	protected function _getRelationships()
	{
		$keyRes = $this->_db->query('SELECT
											TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
										FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
										WHERE REFERENCED_TABLE_NAME IS NOT NULL')->fetchAll(\PDO::FETCH_ASSOC);

		$keys = array();
		foreach($keyRes as $keyData)
		{
			$fromTable  = $keyData['TABLE_NAME'];
			$fromCol    = $keyData['COLUMN_NAME'];
			$toTable    = $keyData['REFERENCED_TABLE_NAME'];
			$toCol      = $keyData['REFERENCED_COLUMN_NAME'];

			if(isset($this->_tables[$fromTable]) && isset($this->_tables[$toTable]))
			{
				$phpName = $this->_generateFkName($fromCol, $this->_tables[$fromTable]['php_name']);
				$this->_tables[$fromTable]['relationships']['toOne'][$fromCol] = array('from_col_php' => $this->_generatePhpName($fromCol), 'from_col' => $fromCol, 'php_name' => $phpName, 'table' => $toTable, 'col' => $toCol, 'col_php' => $this->_generatePhpName($toCol));

				$phpName = $this->_generateFkName($fromCol, $this->_tables[$fromTable]['php_name']) . $this->_tables[$fromTable]['php_name'].'s';
				$this->_tables[$toTable]['relationships']['toMany'][] = array('from_col_php' => $this->_generatePhpName($fromCol), 'php_name' => $phpName, 'thisCol' => $toCol, 'table' => $fromTable, 'table_php' => $this->_generatePhpName($fromTable), 'fromCol' => $fromCol, 'col_php' => $this->_generatePhpName($toCol));
			}
		}

		return $keys;
	}

	protected function _getColumns()
	{
		foreach($this->_tables as $key => &$val)
		{
			$cols = array();
			foreach($this->_db->query('DESCRIBE ' . $key)->fetchAll(\PDO::FETCH_ASSOC) as $column)
			{
				$col                = $this->_processColumn(array(), $column);
				$cols[$col['name']] = $col;
			}

			$val['columns'] = $cols;
		}

	}

	protected function _processColumn($col, $column)
	{
		$col['name']    = $column['Field'];
		$col['php_name']= $this->_generatePhpName($col['name']);
		$matches        = array();

		preg_match('/^([a-zA-Z]+)(\()?([0-9]+)?(\))?/', $column['Type'], $matches);

		$col['type']    = strtolower($matches[1]);
		$col['length']  = isset($matches[3]) ? $matches[3] : 255;
		$col['null']    = strtolower($column['Null']) == 'yes' ? true : false;
		$col['validate']= array();

		if(!$col['null'])
		{
			$col['validate_null'] = true;
		}

		switch($col['type'])
		{
			case 'tinyint':
			case 'smallint':
			case 'int':
			case 'mediumint':
			case 'bigint':
				$col['php_type']    = 'int';
				$col['to_php']      = '_sqlToInt';
				$col['validate_int']= true;
				break;

			case 'float':
			case 'decimal':
				$col['php_type']    = 'float';
				$col['to_php']      = '_sqlToFloat';
				$col['validate_float'] = true;
				break;

			case 'datetime':
			case 'date':
				$col['php_type']    = 'DateTime';
				$col['to_php']      = '_sqlToDateTime';
				$col['to_sql']      = '_dateTimeToSql';
				$col['validate_date'] = true;
			break;

			case 'varchar':
			case 'text':
			default:
				$col['php_type']    = 'string';
				$col['validate_string']  = true;
			break;
		}

		return $col;
	}

	protected function _generatePhpName($sqlName)
	{
		$rtn = $sqlName;
		$rtn = str_replace('_', ' ', $rtn);
		$rtn = ucwords($rtn);
		$rtn = str_replace(' ', '', $rtn);

		return $rtn;
	}

	protected function _generateFkName($sqlName, $tablePhpName)
	{
		$fkMethod = substr($sqlName, 0, strripos($sqlName, '_'));

		if(empty($fkMethod))
		{
			if(substr(strtolower($sqlName), -2) == 'id')
			{
				$fkMethod = substr($sqlName, 0, -2);
			}
			else
			{
				$fkMethod = $tablePhpName;
			}
		}

		return ucwords($fkMethod);
	}
}