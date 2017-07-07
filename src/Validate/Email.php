<?php
/**
 * @author mei
 * @date 2017/6/16
 * @since 2017/6/16 驗證email格式
 */

namespace Validate;

use Respect\Validation\Validator as v;

class Email extends BaseValidate
{
    const INVALID_EMAIL = 'invalid_email';

    protected $messageTemplates = [
        self::INVALID_EMAIL => '{{value}} 不是正確的Email地址'
    ];

    protected function validate($value)
    {
        if(!v::email()->validate($value)){
            $this->error(self::INVALID_EMAIL);
            return false;
        }

        return true;
    }
}