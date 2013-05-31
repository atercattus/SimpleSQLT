SimpleSQLT
==========

[![Build Status](https://travis-ci.org/AterCattus/SimpleSQLT.png)](https://travis-ci.org/AterCattus/SimpleSQLT)

Simple SQL query templater for PHP

[Description in Russian](https://github.com/AterCattus/SimpleSQLT/blob/master/README_ru.md)

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
