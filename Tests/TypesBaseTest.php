<?php
require_once(__DIR__.'/TypesTestParent.php');

use \SimpleSQLT\Templater as SQLT;

class TypesBaseTest extends TypesTestParent
{
    /**
     * {@inheritdoc}
     */
    public function testSetupSQLT()
    {
        return new SQLT(SQLT::VIA_MYSQL); // Features MySQL is not used here
    }

    public function provider()
    {
        $raw = implode('', array_map('chr', range(0, 255)));

        $arr = range(1, 3);
        $hash = array('a'=>'a val', 'b'=>'', 'c'=>'3c val', 'd'=>false, 'e'=>0);

        return array(
            // scalar
            array(':int',       42,     '42'   ),
            array(':integer',   42,     '42'   ),
            array(':int',       -1,     '(-1)' ),
            array(':int',       1.25,   '1'    ),
            array(':int',       '42',   '42'   ),
            array(':int',       '7a',   '7'    ),
            array(':int',       false,  '0'    ),

            array(':float',     42,     '42'   ),
            array(':float',     -1,     '(-1)' ),
            array(':float',     1.25,   '1.25' ),
            array(':float',     '42',   '42'   ),

            array('',           42,     '"42"' ),
            array(':str',       42,     '"42"' ),
            array(':string',    42,     '"42"' ),

            array(':raw',       $raw,   $raw   ),

            // array
            array(':array',     $arr,   '"1", "2", "3"'),
            array(':array:str', $arr,   '"1", "2", "3"'),
            array(':array:int', $arr,   '1, 2, 3'),

            // hash
            array(':hash',      $hash,  '`a`="a val", `b`="", `c`="3c val", `d`="", `e`="0"'),
            array(':hash:int',  $hash,  '`a`=0, `b`=0, `c`=3, `d`=0, `e`=0'),
        );
    }
}
