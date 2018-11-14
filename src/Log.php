<?php
/**
 * @author Evgeniy Makhrov <emakhrov@gmail.com>
 */

namespace lib;

class Log {
    const L_ERROR = 1;
    const L_INFO = 2;
    const L_DEBUG = 3;

    const C_SQL = 1;
    const C_CTRL = 2;

    /** @var LogScreen[][][] */
    static $listeners = [];

    static function add($channel, $level, $listener) {
        self::$listeners[$channel][$level][] = $listener;
    }

    static function msg($channel, $level, $msg) {
        if (!isset(self::$listeners[$channel])) return;
        foreach (self::$listeners[$channel] as $l_level => $listeners) {
            if ($l_level >= $level) {
                foreach ($listeners as $listener) {
                    /** @var $listener LogScreen */
                    $listener->msg($msg);
                }
            }
        }
    }
}

class LogConsole {
    public function msg($msg) {
        echo date('Y-m-d H:i:s') . substr(explode(' ', microtime())[0], 1, 4) . ' ' . getmypid() . ": $msg\n";
    }
}

class LogScreen {
    public function msg($msg) {
        echo "<pre>$msg</pre>\n";
    }
}

class LogMem {
    private $log = [];
    public function msg($msg) {
        $this->log[] = $msg;
    }
    public function getLog() {
        return $this->log;
    }
}

class LogFile {
    private $file;
    public function __construct($file) {
        $this->file = $file;
    }
    public function msg($msg) {
        file_put_contents(
            $this->file,
            date('Y-m-d H:i:s') . '.' . substr(explode(' ', microtime())[0], 2, 3) . ' ' . getmypid() . ": $msg\n",
            FILE_APPEND
        );
    }
}

class LogSs {
    private $name;
    public function __construct($name) {
        $this->name = $name;
    }

    public function msg($msg) {
        StatSlow::error($this->name, $msg);
    }
}
