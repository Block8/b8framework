<?php

namespace b8\Database;

use b8\Database;
use b8\View\Template;

class CodeGenerator
{
    /**
     * @var \b8\Database
     */
    protected $database;

    /**
     * @var Map
     */
    protected $map;

    /**
     * @var array[]
     */
    protected $tables;

    /**
     * @var array
     */
    protected $namespaces;

    /**
     * @var array
     */
    protected $paths;

    /**
     * @var bool
     */
    protected $counts = true;

    /**
     * @param Database $database
     * @param array $namespaces
     * @param $paths
     * @param bool $includeCountQueries
     */
    public function __construct(Database $database, array $namespaces, $paths, $includeCountQueries = true)
    {
        $this->database = $database;
        $this->namespaces = $namespaces;
        $this->paths = $paths;
        $this->map = new Map($this->database);
        $this->tables = $this->map->generate();
        $this->counts = $includeCountQueries;
    }

    protected function getNamespace($modelName)
    {
        return array_key_exists(
            $modelName,
            $this->namespaces
        ) ? $this->namespaces[$modelName] : $this->namespaces['default'];
    }

    public function getPath($namespace)
    {
        return array_key_exists($namespace, $this->paths) ? $this->paths[$namespace] : $this->paths['default'];
    }

    public function generateModels()
    {
        print PHP_EOL . 'GENERATING MODELS' . PHP_EOL . PHP_EOL;

        foreach ($this->tables as $tableName => $table) {
            $namespace = $this->getNamespace($table['php_name']);
            $modelPath = $this->getPath($namespace) . str_replace('\\', '/', $namespace) . '/Model/';
            $basePath = $modelPath . 'Base/';
            $modelFile = $modelPath . $table['php_name'] . '.php';
            $baseFile = $basePath . $table['php_name'] . 'Base.php';

            if (!is_dir($basePath)) {
                @mkdir($basePath, 0777, true);
            }

            $model = $this->processTemplate($tableName, $table, 'ModelTemplate');
            $base = $this->processTemplate($tableName, $table, 'BaseModelTemplate');

            print '-- ' . $table['php_name'] . PHP_EOL;

            if (!is_file($modelFile)) {
                print '-- -- Writing new Model' . PHP_EOL;
                file_put_contents($modelFile, $model);
            }

            print '-- -- Writing base Model' . PHP_EOL;
            file_put_contents($baseFile, $base);
        }
    }

    public function generateStores()
    {
        print PHP_EOL . 'GENERATING STORES' . PHP_EOL . PHP_EOL;

        foreach ($this->tables as $tableName => $table) {
            $namespace = $this->getNamespace($table['php_name']);
            $storePath = $this->getPath($namespace) . str_replace('\\', '/', $namespace) . '/Store/';
            $basePath = $storePath . 'Base/';
            $storeFile = $storePath . $table['php_name'] . 'Store.php';
            $baseFile = $basePath . $table['php_name'] . 'StoreBase.php';

            if (!is_dir($basePath)) {
                @mkdir($basePath, 0777, true);
            }

            $model = $this->processTemplate($tableName, $table, 'StoreTemplate');
            $base = $this->processTemplate($tableName, $table, 'BaseStoreTemplate');

            print '-- ' . $table['php_name'] . PHP_EOL;

            if (!is_file($storeFile)) {
                print '-- -- Writing new Store' . PHP_EOL;
                file_put_contents($storeFile, $model);
            }

            print '-- -- Writing base Store' . PHP_EOL;
            file_put_contents($baseFile, $base);
        }
    }

    public function generateControllers()
    {
        print PHP_EOL . 'GENERATING CONTROLLERS' . PHP_EOL . PHP_EOL;

        @mkdir($this->paths . 'Controller/Base/', 0777, true);

        foreach ($this->tables as $tableName => $table) {
            $namespace = $this->getNamespace($table['php_name']);
            $controllerPath = $this->getPath($namespace) . str_replace('\\', '/', $namespace) . '/Controller/';
            $basePath = $controllerPath . 'Base/';
            $controllerFile = $controllerPath . $table['php_name'] . 'Controller.php';
            $baseFile = $basePath . $table['php_name'] . 'ControllerBase.php';

            if (!is_dir($basePath)) {
                @mkdir($basePath, 0777, true);
            }

            $model = $this->processTemplate($tableName, $table, 'ControllerTemplate');
            $base = $this->processTemplate($tableName, $table, 'BaseControllerTemplate');

            print '-- ' . $table['php_name'] . PHP_EOL;

            if (!is_file($controllerFile)) {
                print '-- -- Writing new Controller' . PHP_EOL;
                file_put_contents($controllerFile, $model);
            }

            print '-- -- Writing base Controller' . PHP_EOL;
            file_put_contents($baseFile, $base);
        }
    }

    protected function processTemplate($tableName, $table, $template)
    {
        $tpl = Template::createFromFile($template, B8_PATH . 'Database/CodeGenerator/');
        $tpl->appNamespace = $this->getNamespace($table['php_name']);
        $tpl->name = $tableName;
        $tpl->table = $table;
        $tpl->counts = $this->counts;

        $callback = function ($args, $view) {
            return $this->getNamespace($view->getVariable($args['model']));
        };

        $tpl->addFunction('get_namespace', $callback);

        return $tpl->render();
    }
}
