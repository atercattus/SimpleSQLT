<?php
namespace SimpleSQLT;

/**
 * Исключения для внутреннего использования
 */
class UnmatchedBrackets extends \UnexpectedValueException {}
class NotAScalarType extends \UnexpectedValueException {}

/**
 *
 */
class Templater
{
    const VIA_MYSQL      = 'MySQL';
    //const VIA_POSTGRESQL = 'PostgreSQL';
    //const VIA_MSSQL      = 'MSSQL';

    /**
     * Границы подстановок и условных блоков.
     * Должны быть односимвольными.
     */
    protected $_boundL = '{';
    protected $_boundR = '}';
    protected $_boundCL = '[';
    protected $_boundCR = ']';

    /**
     * @var string
     */
    protected $_via = '';

    /**
     * @var \SimpleSQLT\Specifics\Specific
     */
    protected $_sqlspec;

    /**
     * @var string
     */
    protected $_sql = '';

    /**
     * @var array
     */
    protected $_fields = array();

    /**
     * @var string
     */
    protected $_undefvar_ph = '';

    /**
     * @var bool
     */
    protected $_dropEmptyLines = false;

    /**
     * @var string
     */
    protected $_internalPresent = '';

    /**
     * @param string $via       Тип БД, для которой подготавливаются запросы
     * @param array $via_params Параметры для конструктора $via
     *
     * @throws \UnexpectedValueException При некорректном $via
     */
    public function __construct($via, $via_params=array())
    {
        try {
            $class = new \ReflectionClass('\\SimpleSQLT\\Specifics\\'.$via);
        }
        catch (\ReflectionException $e) {
            throw new \UnexpectedValueException(sprintf('Unexpected via [%s]', $via));
        }
        $sqlspec = $class->newInstanceArgs($via_params);

        $this->_via = $via;
        $this->_sqlspec = $sqlspec;
    }

    /**
     * Возвращает тип БД, для которой подготавливаются запросы
     *
     * @return string
     */
    public function getVia()
    {
        return $this->_via;
    }

    /**
     * Получение или задание флага удаления пустых (или содержащих только пробельные символы) строк из результата
     *
     * Актуально для запросов с условными блоками
     *
     * @param null|bool $flag
     *
     * @return bool Новое состояние флага
     */
    public function dropEmptyLines($flag=null)
    {
        if ($flag !== null) {
            $this->_dropEmptyLines = (bool)$flag;
        }
        return $this->_dropEmptyLines;
    }

    /**
     * Инициализация шаблонной строкой запроса
     *
     * @param string $sql
     * @param array $fields
     *
     * @return \SimpleSQLT\Templater
     */
    public function sql($sql, $fields=array())
    {
        $this->_sql = $sql;
        $this->_fields = $fields;

        if (count($fields)) {
            $this->bind($fields);
        }

        return $this;
    }

    /**
     * Привязка значений полей с перезаписью
     *
     * @param array $fields
     *
     * @return \SimpleSQLT\Templater
     */
    public function bind($fields)
    {
        $this->_fields = array_merge($this->_fields, $fields);
        return $this;
    }

    public function __toString()
    {
        return $this->str();
    }

    /**
     * Получение результата шаблонизации
     *
     * @return string|bool Результирующая строка или false при фатальной ошибке
     */
    public function str()
    {
        $this->_internalPresent = '';
        $sql = $this->_sql;

        // заменитель в местах с необъявленной переменной
        $r = substr(uniqid(), -5);
        $this->_undefvar_ph = sprintf('_%s__UNDEFINED_VARIABLE__%s_', $r, $r);

        // есть ли в оригинальном шаблоне что-то похожее на условные блоки
        $useCond = (false !== strpos($sql, $this->_boundCL));

        try {
            // выполняю подстановки
            $sql = preg_replace_callback(
                '@'.$this->_boundL.'(?<key>\w+)(:(?<type>\w+))?(:(?<subtype>\w+))?'.$this->_boundR.'@',
                array($this, '_replaceBlockCB'),
                $sql
            );
            // сохраняю текст запроса после подстановки известных значений, но до обработки условных блоков
            $this->_internalPresent = $sql;
        }
        catch (NotAScalarType $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
            // не скалярное значение передано со скалярным типом
            return false;
        }

        // если в оригинальном шаблоне были условные блоки - обрабатываю их
        if ($useCond) {
            $sql = $this->_checkForConditionalBlocks($sql);
        }

        // если остались необъявленные/NULL значения - ругаюсь
        if (false !== strpos($sql, $this->_undefvar_ph)) {
            trigger_error('Undeclared or NULL variable out of conditional block', E_USER_WARNING);
            $sql = '';
        }

        return strlen($sql) ? $sql : false;
    }

    /**
     * Получение внутреннего представления sql шаблона после подстановки значений, но до обработки условных блоков
     *
     * Для отладки
     *
     * @return string
     */
    public function getInternalPresent()
    {
        return str_replace($this->_undefvar_ph, '?', $this->_internalPresent);
    }

    /**
     * @param array $matches
     *
     * @return string
     */
    protected function _replaceBlockCB($matches)
    {
        if (!isset($matches['type'])) {
            $matches['type'] = 'str';
        }
        if (!isset($matches['subtype'])) {
            $matches['subtype'] = null;
        }
        return $this->_replaceBlock($matches['key'], $matches['type'], $matches['subtype']);
    }

    /**
     * Экранирование значения (перевод в строковое представление) в зависимости от указанного типа
     *
     * Поддержка только скалярных типов
     *
     * @param mixed $value
     * @param string $type
     *
     * @throws NotAScalarType Если не скалярное значение передано со скалярным типом
     *
     * @return string
     */
    protected function _escValue($value, $type)
    {
        if (!is_scalar($value)) {
            throw new NotAScalarType('For the scalar variable obtained the value of "'.gettype($value).'" type');
        }

        switch (strtolower($type)) {
            case 'int':
            case 'integer':
                $value = (int)$value;
                if ($value<0) {
                    $value = '('.$value.')'; // (-100500)
                }
                return (string)$value;

            case 'float':
                $value = (float)$value;
                if ($value < 0.0) {
                    $value = '('.$value.')'; // (-4.2)
                }
                return (string)$value;

            case 'id':
                return $this->_sqlspec->quoteID((string)$value);

            case 'str':
            case 'string':
                $ch = '"';
                return $ch.$this->_sqlspec->esc((string)$value).$ch;

            case 'raw':
                return (string)$value;

            default:
                trigger_error('Undefined value type ['.$type.']', E_USER_WARNING);
                // assumes as string
                return $this->_escValue($value, 'str');
        }
    }

    /**
     * Получение строкового представления значения по его имени в словаре и типу
     *
     * @param string $name
     * @param string $type
     * @param null|string $subtype
     *
     * @return string
     */
    protected function _replaceBlock($name, $type, $subtype=null)
    {
        if (!array_key_exists($name, $this->_fields)) {
            trigger_error('Undefined variable ['.$name.']', E_USER_WARNING);
            return $this->_undefvar_ph;
        }
        $value = $this->_fields[$name];
        if ($value === null) {
            return $this->_undefvar_ph;
        }

        // для векторных значений важен тип элементов вектора
        $subtype = $subtype ?: 'str';

        switch (strtolower($type)) {
            case 'array':
                $_value = array();
                foreach ($value as $val) {
                    $_value[] = $this->_escValue($val, $subtype);
                }
                return implode(', ', $_value);
            case 'hash':
                $_value = array();
                foreach ($value as $key => $val) {
                    $_value[] = sprintf('%s=%s', $this->_escValue($key, 'id'), $this->_escValue($val, $subtype));
                }
                return implode(', ', $_value);
            default:
                // для скаляров сразу вызываю _escValue
                return $this->_escValue($value, $type);
        }
    }

    /**
     * Токенизация sql шаблона
     *
     * @param string $sql
     *
     * @return array
     */
    protected function _parseSQL($sql)
    {
        $er = error_reporting(0);
        $tokens = token_get_all('<?php '.$sql);
        error_reporting($er);

        array_shift($tokens); // выкидываю мною добавленный T_OPEN_TAG
        $tokens = array_map(
            function($i) {
                return is_array($i) ? $i[1] : $i;
            },
            $tokens
        );

        return $tokens;
    }

    /**
     * Обработка условных блоков:
     *  - для блоков, где все значения проставлены, просто выкидываю из результата условные границы
     *  - для неполных блоков удаляю все их содержимое вместе с границами
     *
     * Поддерживаются вложенные блоки
     *
     * @param array $tokens Разобранный через _parseSQL() шаблон
     *
     * @throws UnmatchedBrackets Бросается и обрабатывается внутри метода
     */
    protected function _processConditionalBlocks(&$tokens)
    {
        try {
            $opens = array();
            $cnt = count($tokens);

            for ($p = 0; $p<$cnt; ++$p) {
                $cur = $tokens[$p];
                if ($cur == $this->_boundCL) {
                    $opens[] = array('p'=>$p, 'u'=>false);
                }
                elseif ($cur == $this->_boundCR) {
                    $open = array_pop($opens);
                    if (null === $open) {
                        throw new UnmatchedBrackets();
                    }
                    $from = $open['p'];

                    if ($open['u']) { // удаление всего блока
                        while ($from <= $p) {
                            $tokens[$from++] = '';
                        }
                    }
                    else { // удаление только границ
                        $tokens[$from] = ''; // удаление начальной границы
                        $tokens[$p]    = ''; // удаление конечной границы
                    }
                }
                elseif ($cur == $this->_undefvar_ph) {
                    if (0 == ($_cnt = count($opens))) {
                        // необъявленная переменная вне условного блока
                        trigger_error('Undeclared variable out of conditional block', E_USER_WARNING);
                        $tokens = array();
                        return;
                    }
                    $opens[count($opens)-1]['u'] = true;
                }
            }

            if (count($opens)) {
                throw new UnmatchedBrackets();
            }
        }
        catch (UnmatchedBrackets $e) {
            trigger_error(sprintf('Unmatched %s and %s', $this->_boundCL, $this->_boundCR), E_USER_WARNING);
            $tokens = array();
        }
    }

    /**
     * Обработка условных блоков на шаблоне с уже подставленными значениями
     *
     * @param string $sql
     *
     * @return string
     */
    protected function _checkForConditionalBlocks($sql)
    {
        $tokens = $this->_parseSQL($sql);

        // обработка условных блоков (выкидываю не заполненные, оставляю заполненные)
        $this->_processConditionalBlocks($tokens);

        $sql = implode('', $tokens);

        if ($this->_dropEmptyLines) {
            $sql = preg_replace("/(^[\r\n]*|[\r\n]+)[\\s\t]*[\r\n]+/", "\n", $sql);
        }

        return $sql;
    }
}
