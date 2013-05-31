<?php
namespace SimpleSQLT\Specifics;

interface ISpecific
{
    /**
     * @param mixed $v
     *
     * @return mixed
     */
    public function esc($v);

    /**
     * @param string $id
     *
     * @return string
     */
    public function quoteID($id);
}
