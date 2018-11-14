<?php
/**
 * @author Evgeniy Makhrov <emakhrov@gmail.com>
 */

namespace lib;

class Context {
    private static $config, $templates;

    public static function setConfig($config) {
        self::$config = $config;
    }

    public static function getConfig($section = null, $key = null) {
        if (isset($section) && isset($key)) {
            if (isset(self::$config[$section][$key])) return self::$config[$section][$key];
            else return false;
        }
        if (isset($section)) {
            if (isset(self::$config[$section])) return self::$config[$section];
            else return false;
        }
        return self::$config;
    }

    public static function getLinkedConfig($section, $link) {
        return self::getConfig(self::getConfig($section, $link));
    }

    public static function templates($templates = null) {
        if ($templates !== null) self::$templates = $templates;
        return self::$templates;
    }
}
