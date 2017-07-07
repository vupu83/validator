<?php
/**
 * @author mei
 * @date 2017/6/16
 * @since 2017/6/16 檢查數值須小於max
 *
 * $lessThan->isValid($val, 100);
 * $lessThan->isValid($val, ['max'=>100]);
 */
namespace Validate;

use Respect\Validation\Validator as v;

class ValidateFloat extends BaseValidate
{
    const INVALID = 'invalid';

    protected $messageVariables = [];

    protected $messageTemplates = [
        self::INVALID => '{{value}} is invalid, expected string, integer, float',
    ];

    protected function validate($value)
    {
        if(!v::floatVal()->validate($value)){
            $this->error(self::INVALID);
            return false;
        }

        return true;
    }
}