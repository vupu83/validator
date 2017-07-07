<?php
/**
 * @author mei
 * @date 2017/6/16
 * @since 2017/6/16 檢查數值須大於max
 */
namespace Jesda\Validate;

use Respect\Validation\Validator as v;

class GreaterThan extends BaseValidate
{
    protected $messageVariables = [
        'min' => PHP_INT_MIN
    ];

    protected function validate($value)
    {
        if(!v::min($this->min, false)->validate($value)){
            $this->error(self::INVALID);
            return false;
        }

        return true;
    }
}