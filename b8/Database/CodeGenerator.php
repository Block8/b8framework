<?php

namespace b8\Database;
use b8\Database,
	b8\Database\Map,
	b8\View\UserView;

class CodeGenerator
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

	public function generateModels()
	{
		print PHP_EOL . 'GENERATING MODELS' . PHP_EOL . PHP_EOL;
		@mkdir($this->_path . 'Model/Base/', 0777, true);

		foreach($this->_tables as $tableName => $table)
		{
			$model  = $this->_processTemplate($tableName, $table, 'ModelTemplate');
			$base   = $this->_processTemplate($tableName, $table, 'BaseModelTemplate');

			$modelPath  = $this->_path . 'Model/' . $table['php_name'] . '.php';
			$basePath   = $this->_path . 'Model/Base/' . $table['php_name'] . 'Base.php';

			print '-- ' . $table['php_name'] . PHP_EOL;

			if(!is_file($modelPath))
			{
				print '-- -- Writing new Model' . PHP_EOL;
				file_put_contents($modelPath, $model);
			}

			print '-- -- Writing base Model' . PHP_EOL;
			file_put_contents($basePath, $base);
		}
	}

	public function generateStores()
	{
		print PHP_EOL . 'GENERATING STORES' . PHP_EOL . PHP_EOL;
		@mkdir($this->_path . 'Store/Base/', 0777, true);

		foreach($this->_tables as $tableName => $table)
		{
			$model  = $this->_processTemplate($tableName, $table, 'StoreTemplate');
			$base   = $this->_processTemplate($tableName, $table, 'BaseStoreTemplate');

			$modelPath  = $this->_path . 'Store/' . $table['php_name'] . 'Store.php';
			$basePath   = $this->_path . 'Store/Base/' . $table['php_name'] . 'StoreBase.php';

			print '-- ' . $table['php_name'] . PHP_EOL;

			if(!is_file($modelPath))
			{
				print '-- -- Writing new Store' . PHP_EOL;
				file_put_contents($modelPath, $model);
			}

			print '-- -- Writing base Store' . PHP_EOL;
			file_put_contents($basePath, $base);
		}
	}

	public function generateControllers()
	{
		print PHP_EOL . 'GENERATING CONTROLLERS' . PHP_EOL . PHP_EOL;

		@mkdir($this->_path . 'Controller/Base/', 0777, true);

		foreach($this->_tables as $tableName => $table)
		{
			$model  = $this->_processTemplate($tableName, $table, 'ControllerTemplate');
			$base   = $this->_processTemplate($tableName, $table, 'BaseControllerTemplate');

			$modelPath  = $this->_path . 'Controller/' . $table['php_name'] . 'Controller.php';
			$basePath   = $this->_path . 'Controller/Base/' . $table['php_name'] . 'ControllerBase.php';

			print '-- ' . $table['php_name'] . PHP_EOL;

			if(!is_file($modelPath))
			{
				print '-- -- Writing new Controller' . PHP_EOL;
				file_put_contents($modelPath, $model);
			}

			print '-- -- Writing base Controller' . PHP_EOL;
			file_put_contents($basePath, $base);
		}
	}

	protected function _processTemplate($tableName, $table, $template)
	{
		$tpl    = new UserView(file_get_contents(B8_PATH . 'Database/CodeGenerator/' . $template . '.tpl'));
		$tpl->appNamespace  = $this->_ns;
		$tpl->name          = $tableName;
		$tpl->table         = $table;

		return $tpl->render();
	}
}