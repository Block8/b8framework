<?php

/**
 * {@table.php_name} base store for table: {@name}
 */

namespace {@appNamespace}\Store\Base;

use b8\Database;
use b8\Exception\HttpException;
use b8\Store;
use {@appNamespace}\Model\{@table.php_name};

/**
 * {@table.php_name} Base Store
 */
class {@table.php_name}StoreBase extends Store
{
    protected $tableName   = '{@name}';
    protected $modelName   = '\{@appNamespace}\Model\{@table.php_name}';
{if table.primary_key}
    protected $primaryKey  = '{@table.primary_key.column}';

    public function getByPrimaryKey($value, $useConnection = 'read')
    {
        return $this->getBy{@table.primary_key.php_name}($value, $useConnection);
    }
{/if}
{ifnot table.primary_key}

    public function getByPrimaryKey($value, $useConnection = 'read')
    {
        throw new \Exception('getByPrimaryKey is not implemented for this store, as the table has no primary key.');
    }
{/ifnot}
{loop table.columns}
{if item.unique_indexed}

    public function getBy{@item.php_name}($value, $useConnection = 'read')
    {
        if (is_null($value)) {
            throw new HttpException('Value passed to ' . __FUNCTION__ . ' cannot be null.');
        }

        $query = 'SELECT * FROM {@parent.name} WHERE {@item.name} = :{@item.name} LIMIT 1';
        $stmt = Database::getConnection($useConnection)->prepare($query);
        $stmt->bindValue(':{@item.name}', $value);

        if ($stmt->execute()) {
            if ($data = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                return new {@parent.table.php_name}($data);
            }
        }

        return null;
    }
{/if}
{if item.many_indexed}

    public function getBy{@item.php_name}($value, $limit = null, $useConnection = 'read')
    {
        if (is_null($value)) {
            throw new HttpException('Value passed to ' . __FUNCTION__ . ' cannot be null.');
        }

        $add = '';

        if ($limit) {
            $add .= ' LIMIT ' . $limit;
        }

{if counts}
        $query = 'SELECT COUNT(*) AS cnt FROM {@parent.name} WHERE {@item.name} = :{@item.name}' . $add;
        $stmt = Database::getConnection($useConnection)->prepare($query);
        $stmt->bindValue(':{@item.name}', $value);

        if ($stmt->execute()) {
            $res    = $stmt->fetch(\PDO::FETCH_ASSOC);
            $count  = (int)$res['cnt'];
        } else {
            $count = 0;
        }
{/if}
{ifnot counts}
        $count = null;
{/ifnot}

        $query = 'SELECT * FROM {@parent.name} WHERE {@item.name} = :{@item.name}' . $add;
        $stmt = Database::getConnection($useConnection)->prepare($query);
        $stmt->bindValue(':{@item.name}', $value);

        if ($stmt->execute()) {
            $res = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $map = function ($item) {
                return new {@parent.table.php_name}($item);
            };
            $rtn = array_map($map, $res);

            return array('items' => $rtn, 'count' => $count);
        } else {
            return array('items' => array(), 'count' => 0);
        }
    }
{/if}
{/loop}
}
