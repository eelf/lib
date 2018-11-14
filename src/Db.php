<?php
/**
 * @author Evgeniy Makhrov <emakhrov@gmail.com>
 */

namespace lib;

class Db {
    const TABLE_PREFIX = 'TBL_';

    protected static $links = [];

    /** @var DbMysqli */
    protected $link;
    protected $db;
    protected $errno, $error;
    protected $numRows, $affRows, $lastInsertId;
    protected $user, $host, $password;

    public function __construct() {
        $config = Context::getLinkedConfig(static::class, 'db_conf');
        Log::msg(Log::C_SQL, Log::L_INFO, "config for " . static::class . '::db_conf:' . var_export($config, true));
        $this->setCredentials($config);
        $this->db = Context::getConfig(static::class, 'db');
    }

    public function setCredentials($config) {
        $this->host = $config['host'];
        $this->user = $config['user'];
        $this->password = $config['password'];
    }

    public function connect() {
        if ($this->link) return true;

        $key = $this->host . "\n" . $this->user . "\n" . $this->password;
        if (isset(self::$links[$key])) {
            $this->link = self::$links[$key];
            return true;
        }

        Log::msg(Log::C_SQL, Log::L_INFO, "DbMysqli::connect({$this->host}, {$this->user}, {$this->password})");

        $this->link = DbMysqli::connect($this->host, $this->user, $this->password);
        if ($this->link->errno) {
            Log::msg(Log::C_SQL, Log::L_ERROR, "Connect to $this->host failed: " . $this->link->errno . ':' . $this->link->errstr);
            return false;
        }
        self::$links[$key] = $this->link;
        return true;
    }

    public function escape($v, $keep_array = false) {
        if (is_bool($v)) $v = (int)$v;
        else if (is_null($v)) $v = 'NULL';
        else if (is_int($v) || is_float($v)) /*no escape needed*/;
        else if (is_array($v)) {
            if (array_values($v) !== $v) {
                // is_assoc
                foreach ($v as $key => $param) {
                    $k = explode('|', $key);
                    if (isset($k[1]) && $k[0] == 'noescape') {
                        unset($v[$key]);
                        $v[$k[1]] = $param;
                    } else if (isset($k[1]) && $k[0] == 'noparen') {
                        unset($v[$key]);
                        $v[$k[1]] = implode(', ', array_map([$this, 'escape'], $param));
                    } else {
                        $v[$key] = $this->escape($param);
                    }
                }
            } else {
                $v = array_map([$this, 'escape'], $v);
            }
            if (!$keep_array) $v = '(' . implode(',', $v) . ')';
        } else if (is_string($v)) {
            $v = '"' . $this->link->escape($v) . '"';
        } else {
            $desc = is_object($v) ? ("obj:" . get_class($v)) : (is_resource($v) ? ("res:" . get_resource_type($v)) : gettype($v));
            throw new \InvalidArgumentException("Got $desc in SQL params");
        }
        return $v;
    }

    public function bind($sql, $params) {
        $parts = explode('#', $sql);
        $last_idx = count($parts) - 1;
        $result = '';
        $parity = 0;

        foreach ($parts as $idx => $part) {
            if ($idx == 0 || $idx == $last_idx || $idx % 2 == $parity) {
                $result .= $part;
            } else if (isset($params[$part])) {
                $result .= $params[$part];
            } else {
                $parity = $parity ? 0 : 1;
                $result .= '#' . $part;
            }
        }
        return $result;
    }

    public function query($sql, $params = []) {
        if (!$this->connect()) return false;

        static $tables;
        if ($tables === null) {
            $tables = [];
            foreach ((new \ReflectionClass($this))->getConstants() as $const => $value) {
                if (Str::hasPrefix($const, self::TABLE_PREFIX)) {
                    $tables[$const] = ($this->db ? $this->db . '.' : '') . $value;
                }
            }
        }

        $params = $this->escape($params, true);
        $sql = $this->bind($sql, $params + $tables);

        Log::msg(Log::C_SQL, Log::L_INFO, $sql);

//        $time = microtime(true);
        $res = $this->link->query($sql);
//        $time = microtime(true) - $time;

//        Log::msg(Log::C_SQL, Log::L_INFO, var_export($res, true));

        $this->errno = $this->link->errno;
        $this->error = $this->link->errstr;
        $this->lastInsertId = $this->link->insertId();
        $this->affRows = $this->link->affectedRows();

        if ($this->errno) {
            Log::msg(Log::C_SQL, Log::L_ERROR, $this->errno . ': ' . $this->error);
        }

        return $res;
    }

    public function getAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        if (!$result) return false;

        $this->numRows = $this->link->numRows($result);
        $rows = [];
        while ($row = $this->link->fetchAssoc($result)) {
            $rows[] = $row;
        }
        $this->link->free($result);
        return $rows;
    }

    public function getAssoc($sql, $params = [], $key = null) {
        $result = $this->query($sql, $params);
        if (!$result) return false;

        $this->numRows = $this->link->numRows($result);

        $rows = [];

        if (empty($key)) $key = $this->link->fieldName($result, 0);
        $fields_num = $this->link->numFields($result);
        $second_field = $fields_num == 2 ? $this->link->fieldName($result, 1) : null;

        while ($row = $this->link->fetchAssoc($result)) {
            $rows[$row[$key]] = $fields_num != 2 ? $row : $row[$second_field];
        }
        $this->link->free($result);
        return $rows;
    }

    public function getHashlist($sql, $params = [], $key = null) {
        $result = $this->query($sql, $params);
        if (!$result) return false;

        $this->numRows = $this->link->numRows($result);

        $rows = [];

        if (empty($key)) $key = $this->link->fieldName($result, 0);
        $fields_num = $this->link->numFields($result);
        $second_field = $fields_num == 2 ? $this->link->fieldName($result, 1) : null;

        while ($row = $this->link->fetchAssoc($result)) {
            $row_key = $row[$key];
            unset($row[$key]);
            if (!isset($rows[$row_key])) $rows[$row_key] = [];
            $rows[$row_key][] = $fields_num != 2 ? $row : $row[$second_field];
        }
        $this->link->free($result);
        return $rows;
    }

    public function getRow($sql, $params = []) {
        $result = $this->query($sql, $params);
        if (!$result) return false;

        $this->numRows = $this->link->numRows($result);

        $row = $this->link->fetchAssoc($result);
        $this->link->free($result);
        return $row ?: [];
    }

    public function getCol($sql, $params = [], $col = null) {
        $result = $this->query($sql, $params);
        if (!$result) return false;

        $this->numRows = $this->link->numRows($result);

        if ($col === null) $col = $this->link->fieldName($result, 0);
        $rows = [];
        while ($row = $this->link->fetchAssoc($result)) {
            $rows[] = $row[$col];
        }

        $this->link->free($result);
        return $rows;
    }

    public function getOne($sql, $params = [], $col = null) {
        $result = $this->query($sql, $params);
        if (!$result) return false;

        $this->numRows = $this->link->numRows($result);

        $row = $this->link->fetchAssoc($result);
        $row = isset($col) ? $row[$col] : reset($row);
        $this->link->free($result);
        return $row;
    }

    public function getLastInsertId() {
        return $this->lastInsertId;
    }

    public function getAffectedRows() {
        return $this->affRows;
    }

    public function getError() {
        return [$this->link->errno, $this->link->errstr];
    }
}
