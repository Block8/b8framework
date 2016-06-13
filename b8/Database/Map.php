<?php

namespace b8\Database;

use b8\Database;

class Map
{
    protected $db = null;
    protected $tables = array();

    public function __construct(Database $database)
    {
        $this->db = $database;
    }

    public function generate()
    {
        $tables = $this->getTables();


        foreach ($tables as $table) {
            $this->tables[$table] = array();
            $this->tables[$table]['php_name'] = $this->generatePhpName($table);
        }

        $this->getColumns();
        $this->getRelationships();
        $this->getIndexes();

        foreach ($this->tables as $name => &$def) {
            if (count($def['columns']) == 2) {
                $filtered = array_filter($def['columns'], function ($col) {
                    if ($col['is_foreign_key']) {
                        return false;
                    }

                    return  true;
                });

                if (!count($filtered)) {
                    $this->processLinkTable($name, $def);
                }
            }
        }

        $this->tables = array_filter($this->tables);

        foreach ($this->tables as $tableName => &$table) {
            if (!isset($table['relationships']['toMany']) || !is_array($table['relationships']['toMany'])) {
                continue;
            }

            foreach ($table['relationships']['toMany'] as &$rel) {
                print 'Processing: '. $tableName . ' to ' . $rel['table'];

                if (!array_key_exists($rel['table'], $this->tables)) {
                    print ' REMOVING';
                    $rel = null;
                }

                print PHP_EOL;
            }

            $table['relationships']['toMany'] = array_filter($table['relationships']['toMany']);
        }

        return $this->tables;
    }

    protected function getTables()
    {
        $details = $this->db->getDetails();

        $rtn = array();

        foreach ($this->db->query('SHOW TABLES')->fetchAll(\PDO::FETCH_ASSOC) as $tbl) {
            $rtn[] = $tbl['Tables_in_' . $details['db']];
        }

        return $rtn;
    }

    protected function getRelationships()
    {
        foreach ($this->tables as $table => $t) {
            $res = $this->db->query('SHOW CREATE TABLE `' . $table . '`')->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($res as $r) {
                $str = $r['Create Table'];

                $matches = array();
                if (preg_match_all(
                    '/CONSTRAINT\s+\`([a-zA-Z0-9_]+)\`\s+FOREIGN\s+KEY\s+\(\`([a-zA-Z0-9_]+)\`\)\s+REFERENCES\s+\`([a-zA-Z0-9_]+)\`\s+\(\`([a-zA-Z0-9_]+)\`\)(\s+ON (DELETE|UPDATE) (SET NULL|NO ACTION|CASCADE|RESTRICT))?(\s+ON (DELETE|UPDATE) (SET NULL|NO ACTION|CASCADE|RESTRICT))?/',
                    $str,
                    $matches
                )
                ) {
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $fromTable = $table;
                        $fromCol = $matches[2][$i];
                        $toTable = $matches[3][$i];
                        $toCol = $matches[4][$i];
                        $fkName = $matches[1][$i];
                        $fk = array();

                        if (isset($matches[6][$i])) {
                            $fk[$matches[6][$i]] = $matches[7][$i];
                        }

                        if (isset($matches[9][$i])) {
                            $fk[$matches[9][$i]] = $matches[10][$i];
                        }

                        $fk['UPDATE'] = empty($fk['UPDATE']) ? '' : $fk['UPDATE'];
                        $fk['DELETE'] = empty($fk['DELETE']) ? '' : $fk['DELETE'];

                        if (isset($this->tables[$fromTable]) && isset($this->tables[$toTable])) {
                            $phpName = $this->generateFkName($fromCol, $this->tables[$fromTable]['php_name']);

                            $this->tables[$fromTable]['columns'][$fromCol]['is_foreign_key'] = true;
                            $this->tables[$fromTable]['columns'][$fromCol]['fk_to_column'] = $toCol;
                            $this->tables[$fromTable]['columns'][$fromCol]['fk_to_table'] = $toTable;

                            $this->tables[$fromTable]['relationships']['toOne'][$fromCol] = array(
                                'fk_name' => $fkName,
                                'fk_delete' => $fk['DELETE'],
                                'fk_update' => $fk['UPDATE'],
                                'table_php_name' => $this->tables[$toTable]['php_name'],
                                'from_col_php' => $this->generatePhpName($fromCol),
                                'from_col' => $fromCol,
                                'php_name' => $phpName,
                                'table' => $toTable,
                                'col' => $toCol,
                                'col_php' => $this->generatePhpName($toCol)
                            );

                            $singular = $this->tables[$fromTable]['php_name'];
                            $multiple = $singular . 's';
                            $phpName = $this->generateFkName($fromCol, $singular) . $multiple;
                            $this->tables[$toTable]['relationships']['toMany'][] = array(
                                'from_col_php' => $this->generatePhpName($fromCol),
                                'php_name' => $phpName,
                                'thisCol' => $toCol,
                                'table' => $fromTable,
                                'table_php' => $this->generatePhpName($fromTable),
                                'fromCol' => $fromCol,
                                'col_php' => $this->generatePhpName($toCol)
                            );
                        }
                    }
                }
            }
        }
    }

    protected function getColumns()
    {
        foreach ($this->tables as $key => &$val) {
            $cols = array();
            foreach ($this->db->query('DESCRIBE `' . $key . '`')->fetchAll(\PDO::FETCH_ASSOC) as $column) {
                $col = $this->processColumn(array(), $column, $val);
                $cols[$col['name']] = $col;
            }

            $val['columns'] = $cols;
        }

    }

    protected function getIndexes()
    {
        foreach ($this->tables as $key => &$val) {
            $indexes = array();

            foreach ($this->db->query('SHOW INDEXES FROM `' . $key . '`')->fetchAll(\PDO::FETCH_ASSOC) as $idx) {
                if (!isset($indexes[$idx['Key_name']])) {
                    $indexes[$idx['Key_name']] = array();
                    $indexes[$idx['Key_name']]['name'] = $idx['Key_name'];
                    $indexes[$idx['Key_name']]['unique'] = ($idx['Non_unique'] == '0') ? true : false;
                    $indexes[$idx['Key_name']]['columns'] = array();
                }

                $indexes[$idx['Key_name']]['columns'][$idx['Seq_in_index']] = $idx['Column_name'];
            }

            $indexes = array_map(
                function ($idx) {
                    ksort($idx['columns']);
                    $idx['columns'] = implode(', ', $idx['columns']);

                    return $idx;
                },
                $indexes
            );

            $val['indexes'] = $indexes;
        }
    }

    protected function processColumn($col, $column, &$table)
    {
        $col['name'] = $column['Field'];
        $col['php_name'] = $this->generatePhpName($col['name']);
        $matches = array();

        preg_match('/^([a-zA-Z]+)(\()?([0-9\,]+)?(\))?/', $column['Type'], $matches);

        $col['type'] = strtolower($matches[1]);

        if (isset($matches[3])) {
            $col['length'] = $matches[3];
        }

        $col['null'] = strtolower($column['Null']) == 'yes' ? true : false;
        $col['auto'] = strtolower($column['Extra']) == 'auto_increment' ? true : false;

        if ($column['Default'] == 'NULL' || is_null($column['Default'])) {
            $col['default_is_null'] = true;
        } else {
            $col['default_is_null'] = false;
            $col['default'] = $column['Default'];
        }

        if (!empty($column['Key'])) {
            if ($column['Key'] == 'PRI') {
                $col['is_primary_key'] = true;
                $table['primary_key'] = array('column' => $col['name'], 'php_name' => $col['php_name']);
            }

            if ($column['Key'] == 'PRI' || $column['Key'] == 'UNI') {
                $col['unique_indexed'] = true;
            } else {
                $col['many_indexed'] = true;
            }
        }

        $col['validate'] = array();

        if (!$col['null']) {
            $col['validate_null'] = true;
        }

        switch ($col['type']) {
            case 'tinyint':
            case 'smallint':
            case 'int':
            case 'mediumint':
            case 'bigint':
                $col['php_type'] = 'int';
                $col['to_php'] = '_sqlToInt';
                $col['validate_int'] = true;
                break;

            case 'float':
            case 'decimal':
                $col['php_type'] = 'float';
                $col['to_php'] = '_sqlToFloat';
                $col['validate_float'] = true;
                break;

            case 'datetime':
            case 'date':
                $col['php_type'] = 'DateTime';
                $col['to_php'] = '_sqlToDateTime';
                $col['to_sql'] = '_dateTimeToSql';
                $col['validate_date'] = true;
                break;

            case 'varchar':
            case 'text':
            default:
                $col['php_type'] = 'string';
                $col['validate_string'] = true;
                break;
        }

        return $col;
    }

    protected function processLinkTable($name, &$def)
    {
        $col1 = array_shift($def['columns']);
        $col2 = array_shift($def['columns']);

        $this->tables[$col1['fk_to_table']]['relationships']['toMany'][] = [
            'table' => $col2['fk_to_table'],
            'table_php' => $this->generatePhpName($col2['fk_to_table']),
            'thisCol' => $col1['fk_to_column'],
            'fromCol' => $col2['fk_to_column'],
            'link' => [
                'table' => $name,
                'column1' => $col1['name'],
                'column2' => $col2['name'],
            ]
        ];

        $this->tables[$col2['fk_to_table']]['relationships']['toMany'][] = [
            'table' => $col1['fk_to_table'],
            'table_php' => $this->generatePhpName($col1['fk_to_table']),
            'thisCol' => $col2['fk_to_column'],
            'fromCol' => $col1['fk_to_column'],
            'link' => [
                'table' => $name,
                'column1' => $col2['name'],
                'column2' => $col1['name'],
            ]
        ];

        $def = null;
    }

    protected function generatePhpName($sqlName)
    {
        $rtn = $sqlName;
        $rtn = str_replace('_', ' ', $rtn);
        $rtn = ucwords($rtn);
        $rtn = str_replace(' ', '', $rtn);

        return $rtn;
    }

    protected function generateFkName($sqlName, $tablePhpName)
    {
        $fkMethod = substr($sqlName, 0, strripos($sqlName, '_'));

        if (empty($fkMethod)) {
            $fkMethod = (substr(strtolower($sqlName), -2) == 'id') ? substr($sqlName, 0, -2) : $tablePhpName;
        }

        $fkMethod = str_replace('_', ' ', $fkMethod);
        $fkMethod = ucwords($fkMethod);
        $fkMethod = str_replace(' ', '', $fkMethod);

        return $fkMethod;
    }
}