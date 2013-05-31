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

    public function providerTypes()
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

    /**
     * @depends testSetupSQLT
     * @dataProvider providerCondBlocks
     */
    public function testCondBlocks($tmpl, $vars, $expect, $sqlt)
    {
        $out = $sqlt->sql($tmpl, $vars)->str();

        $this->assertTrue($out === $expect, sprintf('"%s" !== "%s"', $out, $expect));
    }

    /**
     * @depends testSetupSQLT
     * @dataProvider providerCondBlocksEmptyLines
     */
    public function testCondBlocksEmptyLines($dropEmpty, $tmpl, $vars, $expect, $sqlt)
    {
        $sqlt->dropEmptyLines($dropEmpty);
        $out = $sqlt->sql($tmpl, $vars)->str();

        $out = implode("\\n", array_map('trim', explode("\n", $out)));

        $this->assertTrue($out === $expect, sprintf('"%s" !== "%s"', $out, $expect));
    }

    public function providerCondBlocks()
    {
        $tmpl =<<<'END'
            SELECT * FROM tbl WHERE `a`={a} [AND `f`>={f:float} [AND `i` IN ({i:array:int})]] [OR `s`={s:int}]
END;
        $tmpl = trim($tmpl);

        return array(
            array(
                '',
                array(),
                ''
            ),
            array(
                $tmpl,
                array('a'=>'Hello World', 'f'=>4.2, 'i'=>range(1, 5), 's'=>7),
                'SELECT * FROM tbl WHERE `a`="Hello World" AND `f`>=4.2 AND `i` IN (1, 2, 3, 4, 5) OR `s`=7'
            ),
            array(
                $tmpl,
                array('a'=>'Hello World', 'f'=>4.2, 'i'=>range(1, 5), 's'=>null),
                'SELECT * FROM tbl WHERE `a`="Hello World" AND `f`>=4.2 AND `i` IN (1, 2, 3, 4, 5) '
            ),
            array(
                $tmpl,
                array('a'=>'Hello World', 'f'=>4.2, 'i'=>null, 's'=>7),
                'SELECT * FROM tbl WHERE `a`="Hello World" AND `f`>=4.2  OR `s`=7'
            ),
            array(
                $tmpl,
                array('a'=>'Hello World', 'f'=>null, 'i'=>range(1, 5), 's'=>7),
                'SELECT * FROM tbl WHERE `a`="Hello World"  OR `s`=7'
            ),
            array(
                $tmpl,
                array('a'=>'Hello World', 'f'=>null, 'i'=>null, 's'=>7),
                'SELECT * FROM tbl WHERE `a`="Hello World"  OR `s`=7'
            ),
            array(
                $tmpl,
                array('a'=>'Hello World', 'f'=>null, 'i'=>null, 's'=>null),
                'SELECT * FROM tbl WHERE `a`="Hello World"  '
            ),
        );
    }

    public function providerCondBlocksEmptyLines()
    {
        $tmpl =<<<'END'
            SELECT
                *
            FROM
                tbl
            WHERE
                `a`={a}
                [
                    AND
                    `f`>={f:float}
                    [
                        AND
                        `i` IN ({i:array:int})
                    ]
                ]
                [
                    OR
                    `s`={s:int}
                ]
END;
        $tmpl = trim($tmpl);

        return array(
            array(
                false,
                $tmpl,
                array('a'=>'Hello World', 'f'=>4.2, 'i'=>range(1, 5), 's'=>7),
                'SELECT\n*\nFROM\ntbl\nWHERE\n`a`="Hello World"\n\nAND\n`f`>=4.2\n\nAND\n`i` IN (1, 2, 3, 4, 5)\n\n\n\nOR\n`s`=7\n'
            ),
            array(
                true,
                $tmpl,
                array('a'=>'Hello World', 'f'=>4.2, 'i'=>range(1, 5), 's'=>7),
                'SELECT\n*\nFROM\ntbl\nWHERE\n`a`="Hello World"\nAND\n`f`>=4.2\nAND\n`i` IN (1, 2, 3, 4, 5)\nOR\n`s`=7\n'
            ),
            array(
                true,
                $tmpl,
                array('a'=>'Hello World', 'f'=>4.2, 'i'=>range(1, 5), 's'=>null),
                'SELECT\n*\nFROM\ntbl\nWHERE\n`a`="Hello World"\nAND\n`f`>=4.2\nAND\n`i` IN (1, 2, 3, 4, 5)\n'
            ),
            array(
                true,
                $tmpl,
                array('a'=>'Hello World', 'f'=>4.2, 'i'=>null, 's'=>7),
                'SELECT\n*\nFROM\ntbl\nWHERE\n`a`="Hello World"\nAND\n`f`>=4.2\nOR\n`s`=7\n'
            ),
            array(
                true,
                $tmpl,
                array('a'=>'Hello World', 'f'=>null, 'i'=>range(1, 5), 's'=>7),
                'SELECT\n*\nFROM\ntbl\nWHERE\n`a`="Hello World"\nOR\n`s`=7\n'
            ),
            array(
                true,
                $tmpl,
                array('a'=>'Hello World', 'f'=>null, 'i'=>null, 's'=>7),
                'SELECT\n*\nFROM\ntbl\nWHERE\n`a`="Hello World"\nOR\n`s`=7\n'
            ),
            array(
                true,
                $tmpl,
                array('a'=>'Hello World', 'f'=>null, 'i'=>null, 's'=>null),
                'SELECT\n*\nFROM\ntbl\nWHERE\n`a`="Hello World"\n'
            ),
        );
    }
}
