<?php
require_once(__DIR__.'/TypesTestParent.php');

use \SimpleSQLT\Templater as SQLT;

class TypesMySQLTest extends TypesTestParent
{
    /**
     * {@inheritdoc}
     */
    public function testSetupSQLT()
    {
        return new SQLT(SQLT::VIA_MYSQL);
    }

    public function providerTypes()
    {
        return array(
            // id
            array(':id',    'a',    '`a`'),
            array(':id',    'a`b',  '`a``b`'),
            // escaping
            array('', "a'b\"c\\d\\ne\\rf\\x00\\x1A", "\"a\\'b\\\"c\\\\d\\\\ne\\\\rf\\\\x00\\\\x1A\""),
        );
    }
}
