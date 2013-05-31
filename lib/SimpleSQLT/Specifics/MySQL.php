<?php
namespace SimpleSQLT\Specifics;

use \SimpleSQLT\Specifics\ISpecific;

class MySQL implements ISpecific
{
    /**
     * {@inheritdoc}
     */
    public function esc($v)
    {
        if (is_array($v)) {
            return array_map(array($this, 'esc'), $v);
        }

        $search  = array(  "\\",   "'",   "\"",  "\n",  "\r",  "\x00",  "\x1A");
        $replace = array("\\\\", "\\'", "\\\"", "\\n", "\\r", "\\x00", "\\x1A");
        return @str_replace($search, $replace, (string)$v);
    }

    /**
     * {@inheritdoc}
     */
    public function quoteID($id)
    {
        return '`'.str_replace('`', '``', $id).'`';
    }
}
