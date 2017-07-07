<?php
/**
 * @author mei
 * @date 2017/6/16
 * @since 2017/6/16 檢查數值須小於max
 *
 * $lessThan->isValid($val, 100);
 * $lessThan->isValid($val, ['max'=>100]);
 */
namespace Jesda\Validate;

use Respect\Validation\Validator as v;

class ValidateDate extends BaseValidate
{
    const INVALID        = 'dateInvalid';

    protected $messageVariables = [
        'format' => 'Y-m-d'
    ];

    protected $messageTemplates = [
        self::INVALID        => "{{value}} is an invalid date",
    ];

    protected function validate($value)
    {
        if(!v::date($this->format)->validate($value)){
            $this->error(self::INVALID);
            return false;
        }

        return true;
    }
}