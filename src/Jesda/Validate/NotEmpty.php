<?php
/**
 * @author mei
 * @date 2017/6/16
 * @since 2017/6/16 驗證email格式
 */

namespace Jesda\Validate;


class NotEmpty extends BaseValidate
{
    const BOOLEAN       = 1;
    const INTEGER       = 2;
    const FLOAT         = 4;
    const STRING        = 8;
    const ZERO          = 16;
    const EMPTY_ARRAY   = 32;
    const NULL          = 64;
    const PHP           = 127;
    const SPACE         = 128;
    const OBJECT        = 256;
    const OBJECT_STRING = 512;
    const OBJECT_COUNT  = 1024;
    const ALL           = 2047;

    const INVALID  = 'notEmptyInvalid';
    const IS_EMPTY = 'isEmpty';

    protected $constants = [
        self::BOOLEAN       => 'boolean',
        self::INTEGER       => 'integer',
        self::FLOAT         => 'float',
        self::STRING        => 'string',
        self::ZERO          => 'zero',
        self::EMPTY_ARRAY   => 'array',
        self::NULL          => 'null',
        self::PHP           => 'php',
        self::SPACE         => 'space',
        self::OBJECT        => 'object',
        self::OBJECT_STRING => 'objectstring',
        self::OBJECT_COUNT  => 'objectcount',
        self::ALL           => 'all',
    ];

    protected $messageTemplates = [
        self::IS_EMPTY => "Value is required and can't be empty",
        self::INVALID  => "Invalid type given. String, integer, float, boolean or array expected",
    ];

    /**
     * 驗證空值項目 預設
     * OBJECT + SPACE + NULL + EMPTY_ARRAY + STRING + FLOAT + BOOLEAN
     * @var integer
     */
    protected $type = 493;

    public function __construct($type = null)
    {
        if(is_numeric($type)){
            $this->setType($type);
        }
    }

    /**
     * Returns the set types
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * 設定空值類型
     *
     * @param  integer|array $type
     * @throws \Exception
     * @return NotEmpty
     */
    public function setType($type = null)
    {
        if (is_array($type)) {
            $detected = 0;
            foreach($type as $value) {
                if (is_int($value)) {
                    $detected += $value;
                } else if (in_array($value, $this->constants)) {
                    $detected += array_search($value, $this->constants);
                }
            }

            $type = $detected;
        } else if (is_string($type) && in_array($type, $this->constants)) {
            $type = array_search($type, $this->constants);
        }

        if (!is_int($type) || ($type < 0) || ($type > self::ALL)) {
            throw new \Exception('Unknown type');
        }

        $this->type = $type;
        return $this;
    }

    /**
     * 驗證空值
     * @param $value
     * @return bool
     */
    protected function validate($value)
    {

        if ($value !== null && !is_string($value) && !is_int($value) && !is_float($value) &&
            !is_bool($value) && !is_array($value) && !is_object($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $type    = $this->getType();
        $object  = false;

        // OBJECT_COUNT (countable object)
        if ($type >= self::OBJECT_COUNT) {
            $type -= self::OBJECT_COUNT;
            $object = true;

            if (is_object($value) && ($value instanceof \Countable) && (count($value) == 0)) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // OBJECT_STRING (object's toString)
        if ($type >= self::OBJECT_STRING) {
            $type -= self::OBJECT_STRING;
            $object = true;

            if ((is_object($value) && (!method_exists($value, '__toString'))) ||
                (is_object($value) && (method_exists($value, '__toString')) && (((string) $value) == ""))) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // OBJECT (object)
        if ($type >= self::OBJECT) {
            $type -= self::OBJECT;
            // fall trough, objects are always not empty
        } else if ($object === false) {
            // object not allowed but object given -> return false
            if (is_object($value)) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // SPACE ('   ')
        if ($type >= self::SPACE) {
            $type -= self::SPACE;
            if (is_string($value) && (preg_match('/^\s+$/s', $value))) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // NULL (null)
        if ($type >= self::NULL) {
            $type -= self::NULL;
            if ($value === null) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // EMPTY_ARRAY (array())
        if ($type >= self::EMPTY_ARRAY) {
            $type -= self::EMPTY_ARRAY;
            if (is_array($value) && ($value == array())) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // ZERO ('0')
        if ($type >= self::ZERO) {
            $type -= self::ZERO;
            if (is_string($value) && ($value == '0')) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // STRING ('')
        if ($type >= self::STRING) {
            $type -= self::STRING;
            if (is_string($value) && ($value == '')) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // FLOAT (0.0)
        if ($type >= self::FLOAT) {
            $type -= self::FLOAT;
            if (is_float($value) && ($value == 0.0)) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // INTEGER (0)
        if ($type >= self::INTEGER) {
            $type -= self::INTEGER;
            if (is_int($value) && ($value == 0)) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        // BOOLEAN (false)
        if ($type >= self::BOOLEAN) {
            $type -= self::BOOLEAN;
            if (is_bool($value) && ($value == false)) {
                $this->error(self::IS_EMPTY);
                return false;
            }
        }

        return true;
    }
}