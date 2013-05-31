SimpleSQLT
==========

[![Build Status](https://travis-ci.org/AterCattus/SimpleSQLT.png)](https://travis-ci.org/AterCattus/SimpleSQLT)

Простой шаблонизатор SQL запросов для PHP

Обрабатывает подстановку скалярных и векторных типизированных placeholder'ов с учетом условных блоков.

## Пример ##
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
получаем:
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
Для <i>null</i> значения внутри условного блока [...]:
```php
$vars['stage'] = null;
$sqlt->dropEmptyLines(true); // выкидывание пустых строк из итогового запроса
$sql = $sqlt->sql($sql_tmpl)->bind($vars)->str();
echo $sql,"\n";
```
получаем:
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
Передача словаря:
```php
$sql_tmpl = 'INSERT INTO tbl SET {fields:hash}';

// PHP 5.4 short array syntax
$sql = $sqlt->sql($sql_tmpl, ['fields'=>['foo'=>'bar', 'spam'=>'xkcd']]);
echo $sql,"\n"; // __toString()
```
получаем:
```sql
INSERT INTO tbl SET `foo`="bar", `spam`="xkcd"
```

## Описание ##
Подстановка (placeholder) описывается выражением:

    {key[:type[:subtype]]}

где [...] - необязательная часть.

### Типы данных ###

<ul>
    <li><i>key</i> - ключ словаря параметров, передаваемых вторым параметром sql() или в bind();</li>
    <li><i>type</i> - тип данных в подстановке (значения, указанные через / - синонимы):
        <ul>
            <li><b>int/integer</b> - целочисленное значение;</li>
            <li><b>float</b> - число с плавающей точкой;</li>
            <li><b>str/string</b> - строка. значение по умолчанию, если <i>type/subtype</i> не указан;</li>
            <li><b>id</b> - название поля БД;</li>
            <li><b>raw</b> - сырые данные, вставляются в запрос как есть;</li>
            <li><b>array</b> - массив значений, тип которых указывается в <i>subtype</i>. значения разделяются запятой;</li>
            <li><b>hash</b> - словарь с типами [<b>id</b> => <b>subtype</b>, ...]. ключи всегда типа <b>id</b>, значения - типа <i>subtype</i>.</li>
        </ul>
    </li>
    <li><i>subtype</i> - тип данных элементов вектора в подстановке.</li>
</ul>
К примеру:

    - {v} равноценно {v:str} и {v:string};
    - {v:array} равноценно "{v0:str}, {v1:str}, ...";
    - {v:array:int} равноценно "{v0:int}, {v1:int}, ...";
    - {v:hash} равноценно "{v0k:id} = {v0v:str}, {v1k:id} = {v1v:str}, ...";
    - {v:hash:raw} равноценно "{v0k:id} = {v0v:raw}, {v1k:id} = {v1v:raw}, ...".

### Условные блоки ###

В шаблонизаторе можно задавать части строки условно-включаемыми в результат.
Содержимое такого блока не отбрасывается из выдачи, только если все подстановки внутри блока выполнены успешно: есть соответствующие ключи, и значения не равны <i>null</i>.
Поддерживаются вложенные условные блоки. В таком случае отбрасывание внутреннего блока автоматически приводит к отбрасыванию внешнего.

Условные блоки задаются внутри [...]:

    a={a} [AND b={b} [OR c={c}]]

Результат:

| a | b    | c    | результат            |
|---|------|------|----------------------|
| 1 | 2    | 3    | 'a=1 AND b=2 OR c=3' |
| 1 | 2    | null | 'a=1 AND b=2 '       |
| 1 | null | 3    | 'a=1 '               |
| 1 | null | null | 'a=1 '               |

### Обработка ошибок ###

При ошибках разбора генерятся PHP ошибки уровня E_USER_WARNING:
<ul>
    <li>При несоответствии скобок;</li>
    <li>При отсутствии в переданных ключах имени, указанного в подстановке;</li>
    <li>При передаче в скалярную подстановку не скалярного значения;</li>
    <li>Указание <i>null</i> значения для подстановки вне условного блока.</li>
</ul>
