<?php
/**
 * @author mei
 * @date 2017/6/16
 * @since 2017/6/16 description
 */
namespace Jesda\Validate;


class IdentityNumber extends BaseValidate
{
    const FORMAT = 'IdFormat';

    protected $messageVariables = [];

    protected $messageTemplates = [
        self::FORMAT => "Id format error"
    ];

    protected function validate($value)
    {
        if (!$this->checkIdNumber($value)) {
            $this->error(self::FORMAT);
            return false;
        }

        return true;
    }

    /**
     * 檢查身分證號
     * @param $idNum
     * @return bool
     */
    protected function checkIdNumber($idNum)
    {
        if ($idNum == "") {
            return false;
        }
        $idNum = strtoupper(trim($idNum));
        $pattern = "/^[A-Z]{1}[12]{1}[[:digit:]]{8}$/";
        if (!preg_match($pattern, $idNum)){
            return false;
        }
        $wd_str = "BAKJHGFEDCNMLVUTSRQPZWYX0000OI";
        $d1 = strpos($wd_str, $idNum[0]) % 10;
        $sum = 0;
        for($i=1;$i<9;$i++) {
            $sum += (int)$idNum[$i] * (9 - $i);
        }
        $sum += $d1 + (int)$idNum[9];
        if($sum % 10 != 0) {
            return false;
        } else {
            return true;
        }
    }

}