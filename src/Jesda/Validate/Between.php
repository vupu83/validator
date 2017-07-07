<?php
/**
 * @author mei
 * @date 2017/6/16
 * @since 2017/6/16 description
 */
namespace Jesda\Validate;

use Respect\Validation\Validator as v;

class Between extends BaseValidate
{
    protected $messageVariables = [
        'min' => PHP_INT_MIN,
        'max' => PHP_INT_MAX
    ];

    protected function validate($value)
    {
        if(!v::between($this->min, $this->max)->validate($value)){
            $this->error(self::INVALID);
            return false;
        }

        return true;
    }
}