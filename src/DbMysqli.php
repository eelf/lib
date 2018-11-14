<?php
/**
 * @author Evgeniy Makhrov <emakhrov@gmail.com>
 */

namespace lib;

class DbMysqli {
    public $link, $errno, $errstr;

    private function __construct($link) {
        $this->link = $link;
        $this->errno = mysqli_errno($link);
        $this->errstr = mysqli_error($link);
    }

    public function escape($query) {
        return mysqli_escape_string($this->link, $query);
    }

    public function query($query) {
        $res = mysqli_query($this->link, $query);
        $this->errno = mysqli_errno($this->link);
        $this->errstr = mysqli_error($this->link);
        return $res;
    }

    public function insertId() {
        return mysqli_insert_id($this->link);
    }

    public function affectedRows() {
        return mysqli_affected_rows($this->link);
    }

    public function numRows($result) {
        return mysqli_num_rows($result);
    }

    public function fetchAssoc($result) {
        return mysqli_fetch_assoc($result);
    }

    public function free($result) {
        mysqli_free_result($result);
    }

    public function fieldName($result, $num) {
        $field_data = mysqli_fetch_field_direct($result, $num);
        if ($field_data) {
            return $field_data->name;
        }
        return false;
    }

    public function numFields($result) {
        return mysqli_num_fields($result);
    }

    public static function connect($host, $user, $password) {
        $link = mysqli_connect($host, $user, $password);
        return new self($link);
    }
}
