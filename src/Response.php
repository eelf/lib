<?php
/**
 * @author Evgeniy Makhrov <emakhrov@gmail.com>
 */

namespace lib;

class Response {
    private $headers = [], $cookies = [], $body = '', $is_done = false;

    public function out() {
        foreach ($this->headers as $header) {
            header($header);
        }
        $this->headers = [];
        foreach ($this->cookies as $cookie) {
            setcookie(
                $cookie['name'],
                $cookie['value'],
                $cookie['expire'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure']
            );
        }
        $this->cookies = [];
        echo $this->body;
        $this->body = '';
    }

    public function header($name, $value) {
        $this->headers[] = "$name: $value";
    }

    public function cookie($name, $value, $expire, $path) {
        $this->cookies[] = [
            'name' => $name,
            'value' => $value,
            'expire' => $expire,
            'path' => $path,
            'domain' => null,
            'secure' => null,
        ];
    }

    public function body($body) {
        $this->body = $body;
    }

    public function redirect($location) {
        if (StatSlow::enabled()) {
            $this->out();
            echo "<div style='font: 18px monospace;'><a href='$location'>$location</a></div><pre>";
            echo (new \Exception())->getTraceAsString();
            StatSlow::displayErrors();
            echo "</pre>";
        } else {
            $this->headers[] = "Location: $location";
        }
        $this->is_done = true;
        return $this;
    }

    public function isDone() {
        return $this->is_done;
    }
}
