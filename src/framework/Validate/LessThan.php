<?php
/**
 * @author mei
 * @date 2017/6/16
 * @since 2017/6/16 檢查數值須小於max
 *
 * $lessThan->isValid($val, 100);
 * $lessThan->isValid($val, ['max'=>100]);
 */
namespace framework\Validate;

use Respect\Validation\Validator as v;

class LessThan extends BaseValidate
{
    protected $messageVariables = [
        'max' => PHP_INT_MAX
    ];

    protected function validate($value)
    {
        if(!v::max($this->max, false)->validate($value)){
            $this->error(self::INVALID);
            return false;
        }

        return true;
    }
}