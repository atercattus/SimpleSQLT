SimpleSQLT
==========

[![Build Status](https://travis-ci.org/AterCattus/SimpleSQLT.png)](https://travis-ci.org/AterCattus/SimpleSQLT)

Simple SQL query templater for PHP

[Description in Russian](https://github.com/AterCattus/SimpleSQLT/blob/master/README_ru.md)

<i>Sorry for my bad English :)</i>

## Usage ##
```php
<?php
// ...
use \SimpleSQLT\Templater as SQLT;

$sqlt = new SQLT(SQLT::VIA_MYSQL);

$sql_tmpl = <<<'END'
SELECT
    {fields:array:id}
FROM
    {db:id}.{tbl:id}
WHERE
    `sect_id` = {section:int}
    [
    AND
    `stage` = {stage}
    ]
    AND
    `status` IN ({statuses:array:int})
END;

$vars = array(
    'db'       => 'db_name',
    'tbl'      => 'table',
    'fields'   => array('id', 'name', 'status'),
    'section'  => 42,
    'stage'    => 'queued',
    'statuses' => range(1, 3)
);

$sql = $sqlt->sql($sql_tmpl, $vars)->str();
echo $sql,"\n";
```
result:
```sql
SELECT
    `id`, `name`, `status`
FROM
    `db_name`.`table`
WHERE
    `sect_id` = 42

    AND
    `stage` = "queued"

    AND
    `status` IN (1, 2, 3)
```
For **null** value inside conditional block [...]:
```php
$vars['stage'] = null;
$sqlt->dropEmptyLines(true); // cut off the blank lines in the result
$sql = $sqlt->sql($sql_tmpl, $vars)->str();
echo $sql,"\n";
```
result:
```sql
SELECT
    `id`, `name`, `status`
FROM
    `db_name`.`table`
WHERE
    `sect_id` = 42
    AND
    `status` IN (1, 2, 3)
```
Usage of fields dictionary:
```php
$sql_tmpl = 'INSERT INTO tbl SET {fields:hash}';

// PHP 5.4 short array syntax
$sql = $sqlt->sql($sql_tmpl, ['fields'=>['foo'=>'bar', 'spam'=>'xkcd']]);
echo $sql,"\n"; // __toString()
```
result:
```sql
INSERT INTO tbl SET `foo`="bar", `spam`="xkcd"
```

## Description ##
The placeholder is described by the expression:

    {key[:type[:subtype]]}

where [...] - an optional part.

### Types ###

<ul>
    <li><i>key</i> - key of parameters dictionary to be passed as the second parameter sql() or via bind();</li>
    <li><i>type</i> - data type in placeholder (the values specified by / are synonyms):
        <ul>
            <li><b>int/integer</b> - integer value;</li>
            <li><b>float</b> - float value;</li>
            <li><b>str/string</b> - string. this is default data type if not explicitly specified <i>type/subtype</i>;</li>
            <li><b>id</b> - DB field name;</li>
            <li><b>raw</b> - raw data is added to the query as is;</li>
            <li><b>array</b> - array of values of the type specified in the <i>subtype</i>. values ​​are separated by commas;</li>
            <li><b>hash</b> - dictionary [<b>id</b> => <b>subtype</b>, ...]. keys are always type <b>id</b>, values of type <i>subtype</i>.</li>
        </ul>
    </li>
    <li><i>subtype</i> - data type of elements of array/hash in a placeholder.</li>
</ul>
For example:

    - {v} is equivalent to {v:str} и {v:string};
    - {v:array} is equivalent to "{v0:str}, {v1:str}, ...";
    - {v:array:int} is equivalent to "{v0:int}, {v1:int}, ...";
    - {v:hash} is equivalent to "{v0k:id} = {v0v:str}, {v1k:id} = {v1v:str}, ...";
    - {v:hash:raw} is equivalent to "{v0k:id} = {v0v:raw}, {v1k:id} = {v1v:raw}, ...".

### Conditional blocks ###

In a templating engine portion of the string can be specified conditionally included in the results.
The contents of such a block is not discarded from the output only if all substitutions within the block successful: there are corresponding keys and values ​​are not equal <i>null</i>.
It supports nested conditional blocks. In this case, discarding a nesting block to automatically causes to be discarded the external.

The conditional blocks is described by the expression [...]:

    a={a} [AND b={b} [OR c={c}]]

For example:

| a | b    | c    | result               |
|---|------|------|----------------------|
| 1 | 2    | 3    | 'a=1 AND b=2 OR c=3' |
| 1 | 2    | null | 'a=1 AND b=2 '       |
| 1 | null | 3    | 'a=1 '               |
| 1 | null | null | 'a=1 '               |

### Error handling ###

When a parsing errors occurs are generated PHP error E_USER_WARNING:
<ul>
    <li>Unmatched brackets;</li>
    <li>If there is no key of placeholder in the dictionary;</li>
    <li>When passed to a scalar placeholder is not a scalar value;</li>
    <li>Specifying <i>null</i> value for the substitution out of conditional block.</li>
</ul>
