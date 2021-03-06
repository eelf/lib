<?php
/**
 * @author Evgeniy Makhrov <emakhrov@gmail.com>
 */

namespace lib;

class Request {
    private $url_parts, $first;

    public function init() {
        $url = (isset($_SERVER['HTTPS']) ? 'https:' : 'http:') . '//' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
        $this->url_parts = parse_url($url);
        $this->url_parts['path_exp'] = explode('/', preg_replace("#^/+|/+$|(/)/*#", '$1', $this->url_parts['path']));

        $this->first = $this->url_parts['path_exp'][0] ?? null;
    }

    public function first() {
        return $this->first;
    }

    public function get($name) {
        return isset($_GET[$name]) ? $_GET[$name] : null;
    }

    public function post($name) {
        return isset($_POST[$name]) ? $_POST[$name] : null;
    }

    public function cookie($name) {
        return $_COOKIE[$name] ?? null;
    }

    public function comps() {
        return $this->url_parts['path_exp'];
    }

    public function comp($idx) {
        return $this->url_parts['path_exp'][$idx] ?? null;
    }

    public function path() {
        return $this->url_parts['path'];
    }

    public function postData() {
        return file_get_contents("php://input");
    }
}
