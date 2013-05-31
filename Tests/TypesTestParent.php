<?php
// instead of autoloader's definition
$path = __DIR__.'/../lib/SimpleSQLT/';
require_once($path.'Templater.php');
require_once($path.'Specifics/ISpecific.php');
require_once($path.'Specifics/MySQL.php');

use \SimpleSQLT\Templater as SQLT;

class TypesTestParent extends PHPUnit_Framework_TestCase
{
    /**
     * @return \SimpleSQLT\Templater
     */
    public function testSetupSQLT()
    {
        $this->assertTrue(false, 'testSetupSQLT method is not implemented in the child');
    }

    /**
     * @depends testSetupSQLT
     * @dataProvider providerTypes
     */
    public function testTypes($type, $val, $expect, $sqlt)
    {
        $tmpl = sprintf('{v%s}', $type);
        $out = $sqlt->sql($tmpl, array('v'=>$val))->str();

        $this->assertTrue($out === $expect, sprintf('"%s" !== "%s"', $out, $expect));
    }

    public function providerTypes()
    {
        return array();
    }
}
