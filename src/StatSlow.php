<?php
/**
 * @author Evgeniy Makhrov <emakhrov@gmail.com>
 */

namespace lib;

class StatSlow {
    protected static $ss = [];
    protected static $ss_enabled = false;

    public static function enabled($set = null) {
        if ($set !== null) self::$ss_enabled = (bool)$set;
        return self::$ss_enabled;
    }

    public static function error($errno, $error) {
        if (!self::$ss_enabled) return;
        static $errnos;
        if (!$errnos) {
            foreach ([
                'E_ERROR', 'E_WARNING', 'E_PARSE', 'E_NOTICE', 'E_CORE_ERROR', 'E_CORE_WARNING', 'E_COMPILE_ERROR',
                'E_COMPILE_WARNING', 'E_USER_ERROR', 'E_USER_WARNING', 'E_USER_NOTICE', 'E_STRICT', 'E_RECOVERABLE_ERROR',
                'E_DEPRECATED', 'E_USER_DEPRECATED'
            ] as $er) {
                $errnos[constant($er)] = $er;
            }
        }
        self::$ss[] = [$errnos[$errno] ?? $errno, $error, (new \Exception())->getTraceAsString()];
    }

    public static function getErrors() {
        return self::$ss;
    }

    public static function displayErrors() {
        if (empty(self::$ss)) return;
        echo <<<'HEAD'
        <style type="text/css">
        .ss_tbl {
            font: 11px/11px "Andale Mono" "Lucida Console" sans-serif;
            background: #ccf;
        }
        .ss_trc {
            cursor: pointer;
            border-bottom: 1px dashed #88c;
        }
        </style>
        <table class="ss_tbl">
        <script type="text/javascript">
        function bt_toggle(id) {
            var el = document.getElementById(id);
            el.style.display = (el.style.display == "none") ? "block" : "none";
        }
        </script>
HEAD;
        $prefix = uniqid('bt_');
        foreach (self::$ss as $idx => $s) {
            $bt_id = $prefix . $idx;
            $s[2] = htmlspecialchars($s[2]);
            $bg = '#88c';
            if (substr($s[0], 0, 2) == 'E_') $bg = '#f88';
            echo <<<BODY
<tr valign="top">
    <td style="background: $bg">$s[0]</td>
    <td>$s[1]</td>
    <td style="white-space: pre;background: #8cc"><span onclick="bt_toggle('$bt_id')" class="ss_trc">trace</span><span style="display: none;" id="$bt_id">$s[2]</span></td>
</tr>
BODY;
        }
        echo '</table>';
        self::$ss = [];
    }
}
