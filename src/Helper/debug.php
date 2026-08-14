<?php

namespace EntelisTeam\Lbaf\Core\Helper;
/**
 * @deprecated
 */
class debug
{
    static function getCaller()
    {
        return debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3)[2] ?? null;
    }

}