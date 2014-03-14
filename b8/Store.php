<?php

namespace b8;

use b8\Cache;
use b8\Config;
use b8\Database;
use b8\Database\Query;
use b8\Exception\HttpException;

abstract class Store
{
    protected $cacheEnabled = true;
    protected $cacheType = Cache::TYPE_REQUEST;
    protected $modelName;
    protected $tableName;
    protected $primaryKey;

    public function __construct()
    {
        if (method_exists($this, 'init')) {
            $this->init();
        }
    }

    abstract public function getByPrimaryKey($key, $useConnection = 'read');

    protected function setCache($modelId, Model $obj)
    {
        if (!$this->cacheEnabled) {
            return null;
        }

        if (!is_null($this->cacheType)) {
            $cache = Cache::getCache($this->cacheType);
            $cache->set($this->modelName . '::' . $modelId, $obj);
        }
    }

    protected function getFromCache($modelId)
    {
        if (!$this->cacheEnabled) {
            return null;
        }

        if (!is_null($this->cacheType)) {
            $cache = Cache::getCache($this->cacheType);
            return $cache->get($this->modelName . '::' . $modelId, null);
        }

        return null;
    }

    public function save(Model $obj, $saveAllColumns = false)
    {
        if (!isset($this->primaryKey)) {
            throw new HttpException\BadRequestException('Save not implemented for this store.');
        }

        if (!($obj instanceof $this->modelName)) {
            throw new HttpException\BadRequestException(get_class($obj) . ' is an invalid model type for this store.');
        }

        $data = $obj->getDataArray();

        if (isset($data[$this->primaryKey])) {
            $rtn = $this->saveByUpdate($obj, $saveAllColumns);
        } else {
            $rtn = $this->saveByInsert($obj, $saveAllColumns);
        }

        return $rtn;
    }

    public function saveByUpdate(Model $obj, $saveAllColumns = false)
    {
        $rtn = null;
        $data = $obj->getDataArray();
        $modified = ($saveAllColumns) ? array_keys($data) : $obj->getModified();

        $updates = array();
        $update_params = array();
        foreach ($modified as $key) {
            $updates[] = $key . ' = :' . $key;
            $update_params[] = array($key, $data[$key]);
        }

        if (count($updates)) {
            $query = 'UPDATE ' . $this->tableName . '
											SET ' . implode(', ', $updates) . '
											WHERE ' . $this->primaryKey . ' = :primaryKey';
            $stmt = Database::getConnection('write')->prepare($query);

            foreach ($update_params as $update_param) {
                $stmt->bindValue(':' . $update_param[0], $update_param[1]);
            }

            $stmt->bindValue(':primaryKey', $data[$this->primaryKey]);
            $stmt->execute();

            $enabled = $this->cacheEnabled;
            $this->cacheEnabled = false;
            $rtn = $this->getByPrimaryKey($data[$this->primaryKey], 'write');
            $this->cacheEnabled = $enabled;

            $this->setCache($data[$this->primaryKey], $rtn);
        } else {
            $rtn = $obj;
        }

        return $rtn;
    }

    public function saveByInsert(Model $obj, $saveAllColumns = false)
    {
        $rtn = null;
        $data = $obj->getDataArray();
        $modified = ($saveAllColumns) ? array_keys($data) : $obj->getModified();

        $cols = array();
        $values = array();
        $qParams = array();
        foreach ($modified as $key) {
            $cols[] = $key;
            $values[] = ':' . $key;
            $qParams[':' . $key] = $data[$key];
        }

        if (count($cols)) {
            $colString = implode(', ', $cols);
            $valString = implode(', ', $values);

            $query = 'INSERT INTO ' . $this->tableName . ' (' . $colString . ') VALUES (' . $valString . ')';
            $stmt = Database::getConnection('write')->prepare($query);

            if ($stmt->execute($qParams)) {
                $modelId = !empty($data[$this->primaryKey]) ? $data[$this->primaryKey] : Database::getConnection(
                    'write'
                )->lastInsertId();

                $enabled = $this->cacheEnabled;
                $this->cacheEnabled = false;
                $rtn = $this->getByPrimaryKey($modelId, 'write');

                $this->cacheEnabled = $enabled;
                $this->setCache($modelId, $rtn);
            }
        }

        return $rtn;
    }

    public function delete(Model $obj)
    {
        if (!isset($this->primaryKey)) {
            throw new HttpException\BadRequestException('Delete not implemented for this store.');
        }

        if (!($obj instanceof $this->modelName)) {
            throw new HttpException\BadRequestException(get_class($obj) . ' is an invalid model type for this store.');
        }

        $data = $obj->getDataArray();

        $stmt = Database::getConnection('write')->prepare(
            'DELETE FROM ' . $this->tableName . ' WHERE ' . $this->primaryKey . ' = :primaryKey'
        );
        $stmt->bindValue(':primaryKey', $data[$this->primaryKey]);
        $stmt->execute();

        $this->setCache($data[$this->primaryKey], null);

        return true;
    }

    /**
     *
     */
    protected function fieldCheck($field)
    {
        if (empty($field)) {
            throw new HttpException('You cannot have an empty field name.');
        }

        if (strpos($field, '.') === false) {
            return $this->tableName . '.' . $field;
        }

        return $field;
    }

    /**
     * @param Query $query
     * @param array $options
     */
    public function handleQueryOptions(Query &$query, array $options)
    {
        if (array_key_exists('limit', $options)) {
            $query->limit($options['limit']);
        }

        if (array_key_exists('offset', $options)) {
            $query->offset($options['offset']);
        }

        if (array_key_exists('order', $options)) {
            if (is_string($options['order'])) {
                $options['order'] = array($options['order']);
            }

            foreach ($options['order'] as $order) {
                $query->order($order[0], $order[1]);
            }
        }
    }

    protected function getNamespace($class)
    {
        $config = Config::getInstance();
        $namespaces = $config->get('app.namespaces', ['default' => $config->get('b8.app.namespace')]);

        if (array_key_exists($class, $namespaces)) {
            return $namespaces[$class];
        }

        return $namespaces['default'];
    }
}
