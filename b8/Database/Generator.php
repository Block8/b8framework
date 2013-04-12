<?php

/**
 * Database generator updates a database to match a set of Models.
 */

namespace b8\Database;
use b8\Database;

class Generator
{
	protected $_db      = null;
	protected $_map     = null;
	protected $_tables  = null;
	protected $_ns      = null;
	protected $_path    = null;

	public function __construct(Database $db, $namespace, $path)
	{
		$this->_db      = $db;
		$this->_ns      = $namespace;
		$this->_path    = $path;
		$this->_map     = new Map($this->_db);
		$this->_tables  = $this->_map->generate();
	}

	public function generate()
	{
		$di = new \DirectoryIterator($this->_path);

		$this->_todo = array();
		foreach($di as $file)
		{
			if($file->isDot())
			{
				continue;
			}

			if($file->getExtension() != 'php')
			{
				continue;
			}

			$modelName = '\\' . $this->_ns . '\\Model\\Base\\' . str_replace('.php', '', $file->getFilename());

			require_once($this->_path . $file->getFilename());
			$cls    = new $modelName();
			$cols   = $cls->columns;
			$idxs   = $cls->indexes;
			$fks    = $cls->foreignKeys;
			$tbl    = $cls->getTableName();
			$isNewTable = false;

			if(!array_key_exists($tbl, $this->_tables))
			{
				$this->_createTable($tbl, $cols, $idxs, $fks);
				continue;
			}
			else
			{
				$table = $this->_tables[$tbl];
				//$this->_updateColumns($table, $cols);
				//$this->_updateIndexes($table, $idxs);
				//$this->_updateRelationships($table, $fks);
			}
		}

		foreach($this->_todo['create'] as $query)
		{
			$this->_db->query($query);
		}

		foreach($this->_todo['index'] as $query)
		{
			$this->_db->query($query);
		}

		foreach($this->_todo['fk'] as $query)
		{
			$this->_db->query($query);
		}
	}

	protected function _createTable($tbl, $cols, $idxs, $fks)
	{
		$defs = array();
		$pks = array();
		foreach($cols as $colName => $def)
		{
			$add = $colName . ' ' . $def['type'];

			switch($def['type'])
			{
				case 'text':
				case 'longtext':
				case 'mediumtext':
				case 'date':
				case 'datetime':
				case 'float':
				break;

				default:
					$add .= '(' . $def['length'] . ')';
				break;
			}

			if(empty($def['nullable']) || !$def['nullable'])
			{
				$add .= ' NOT NULL ';
			}

			if(!empty($def['default']))
			{
				$add .= ' DEFAULT ' . (is_numeric($def['default']) ? $def['default'] : '\'' . $def['default'] . '\'');
			}

			if(!empty($def['auto_increment']) && $def['auto_increment'])
			{
				$add .= ' AUTO_INCREMENT ';
			}

			if(!empty($def['primary_key']) && $def['primary_key'])
			{
				$pks[] = $colName;
			}

			$defs[] = $add;
		}

		if(count($pks))
		{
			$defs[] = 'PRIMARY KEY (' . implode(', ', $pks) . ')';
		}

		$stmt = 'CREATE TABLE ' . $tbl . ' (' . PHP_EOL;
		$stmt .= implode(", \n", $defs);

		$stmt .= PHP_EOL . ') ENGINE=InnoDB DEFAULT CHARSET=utf8';
		$stmt .= PHP_EOL;

		$this->_todo['create'][] = $stmt;

		foreach($idxs as $name => $idx)
		{
			if($name == 'PRIMARY')
			{
				continue;
			}

			$this->_addIndex($tbl, $name, $idx);
		}

		foreach($fks as $name => $fk)
		{
			$this->_addFk($tbl, $name, $fk);
		}
	}

	protected function _addIndex($table, $name, $idx)
	{
		if($name == 'PRIMARY')
		{
			$q = 'ALTER TABLE `' . $table . '` ADD PRIMARY KEY(' . $idx['columns'] . ')';
		}
		else
		{
			$q = 'CREATE ' . ($idx['unique'] ? 'UNIQUE' : '') . ' INDEX `' . $name . '` ON `' . $table . '` (' . $idx['columns'] . ')';
		}

		$this->_todo['index'][] = $q;
	}

	protected function _alterIndex($table, $name, $idx)
	{
		if($name == 'PRIMARY')
		{
			$q = 'ALTER TABLE `' . $table . '` DROP PRIMARY KEY, ADD PRIMARY KEY(' . $idx['columns'] . ')';
			$this->_todo['index'][] = $q;
			return;
		}

		$this->_dropIndex($table, $name);
		$this->_addIndex($table, $name, $idx);
	}

	protected function _dropIndex($table, $idxName)
	{
		$q = 'DROP INDEX `' . $idxName . '` ON `' . $table . '`';
		$this->_todo['index'][] = $q;
	}

	protected function _addFk($table, $name, $fk)
	{
		$q = 'ALTER TABLE `' . $table . '` ADD FOREIGN KEY `' . $name . '` (`' . $fk['local_col'] . '`) REFERENCES `'.$fk['table'].'` (`'.$fk['col'].'`)';

		if(!empty($fk['delete']))
		{
			$q .= ' ON DELETE ' . $fk['delete'] . ' ';
		}

		if(!empty($fk['update']))
		{
			$q .= ' ON UPDATE ' . $fk['update'] . ' ';
		}

		$this->_todo['fk'][] = $q;
	}

	protected function _alterFk($table, $name, $fk)
	{
		$this->_dropFk($table, $name);
		$this->_addFk($table, $name, $fk);
	}

	protected function _dropFk($table, $name)
	{
		$q = 'ALTER TABLE `'.$table.'` DROP FOREIGN KEY `' . $name . '`';
		print $q . PHP_EOL;
		$this->_todo['fk'][] = $q;
	}
}