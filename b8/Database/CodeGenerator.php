<?php

namespace b8\Database;

use b8\Config;
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
        if (array_key_exists($modelName, $this->namespaces)) {
            return $this->namespaces[$modelName];
        } elseif (isset($this->namespaces['default'])) {
            return $this->namespaces['default'];
        } else {
            return null;
        }
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

            if (is_null($namespace)) {
                $msg = 'No namespace defined for ' . $table['php_name'] . ' - What namespace should it go in?';
                $namespace = $this->ask($msg, array_keys($this->paths));
                $this->namespaces[$table['php_name']] = $namespace;
            }

            $modelPath = $this->getPath($namespace) . 'Model/';
            $basePath = $modelPath . 'Base/';
            $modelFile = $modelPath . $table['php_name'] . '.php';
            $baseFile = $basePath . $table['php_name'] . 'Base.php';

            if (!is_dir($basePath)) {
                $old = umask(0);
                @mkdir($basePath, 02775, true);
                umask($old);
            }

            $model = $this->processTemplate($tableName, $table, 'ModelTemplate');
            $base = $this->processTemplate($tableName, $table, 'BaseModelTemplate');

            print '-- ' . $table['php_name'] . PHP_EOL;

            if (!is_file($modelFile)) {
                print '-- -- Writing new Model: ' . $table['php_name'] . PHP_EOL;
                print '-- -- -- ' . $modelFile . PHP_EOL;

                file_put_contents($modelFile, $model);
            }

            print '-- -- Writing base Model: ' . $table['php_name'] . PHP_EOL;
            print '-- -- -- ' . $baseFile . PHP_EOL;
            file_put_contents($baseFile, $base);
        }
    }

    public function generateStores()
    {
        print PHP_EOL . 'GENERATING STORES' . PHP_EOL . PHP_EOL;

        foreach ($this->tables as $tableName => $table) {
            $namespace = $this->getNamespace($table['php_name']);

            if (is_null($namespace)) {
                $msg = 'No namespace defined for ' . $table['php_name'] . ' - What namespace should it go in?';
                $namespace = $this->ask($msg, array_keys($this->paths));
                $this->namespaces[$table['php_name']] = $namespace;
            }

            $storePath = $this->getPath($namespace) . 'Store/';
            $basePath = $storePath . 'Base/';
            $storeFile = $storePath . $table['php_name'] . 'Store.php';
            $baseFile = $basePath . $table['php_name'] . 'StoreBase.php';

            if (!is_dir($basePath)) {
                $old = umask(0);
                @mkdir($basePath, 02775, true);
                umask($old);
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

        @mkdir($this->paths . 'Controller/Base/', 2775, true);

        foreach ($this->tables as $tableName => $table) {
            $namespace = $this->getNamespace($table['php_name']);
            $controllerPath = $this->getPath($namespace) . 'Controller/';
            $basePath = $controllerPath . 'Base/';
            $controllerFile = $controllerPath . $table['php_name'] . 'Controller.php';
            $baseFile = $basePath . $table['php_name'] . 'ControllerBase.php';

            if (!is_dir($basePath)) {
                @mkdir($basePath, 2775, true);
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
        $config = Config::getInstance();
        $appNamespace = $config->get('b8.app.namespace');

        if (!class_exists($appNamespace . '\\Model')) {
            $appNamespace = 'b8';
        }

        $tpl = Template::createFromFile($template, B8_PATH . 'Database/CodeGenerator/');
        $tpl->appNamespace = $appNamespace;
        $tpl->itemNamespace = $this->getNamespace($table['php_name']);
        $tpl->name = $tableName;
        $tpl->table = $table;
        $tpl->counts = $this->counts;

        $callback = function ($args, $view) {
            return $this->getNamespace($view->getVariable($args['model']));
        };

        $tpl->addFunction('get_namespace', $callback);

        return $tpl->render();
    }

    protected function ask($question, $options)
    {
        print $question . PHP_EOL;
        foreach ($options as $key => $value) {
            print ($key + 1) . '. ' . $value . PHP_EOL;
        }

        print 'Enter the number representing your choice: ';

        $stdin = fopen('php://stdin', 'r');
        $rtn = fgets($stdin);
        fclose($stdin);

        $rtn = intval(trim($rtn));


        if ($rtn == 0 || !array_key_exists($rtn - 1, $options)) {
            return $this->ask($question, $options);
        }

        return $options[$rtn - 1];
    }
}
