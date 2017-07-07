<?php
/**
 * @author mei
 * @date 2017/6/14
 * @since 2017/6/14 description
 */

namespace framework\Validate;


abstract class BaseValidate
{
    const INVALID = 'invalid';
    /**
     * 錯誤訊息樣板
     * {{value}} 帶入驗證值
     * $messageVariables = [key=>val]  {{key}} 帶入其他變數
     * @var array
     */
    protected $messageTemplates = [
        self::INVALID => '{{value}} is invalid',
    ];

    /**
     * 其他變數
     * @var array
     */
    protected $messageVariables = [];

    /**
     * 錯誤訊息
     * @var array
     */
    protected $messages = [];

    /**
     * 驗證值
     * @var mixed
     */
    protected $value;

    /**
     * 驗證值的欄位名稱
     * @var string
     */
    protected $fieldName;

    /**
     * 翻譯工具
     * @var
     */
    protected $translator;

    /**
     * 翻譯語言 預設null 依照翻譯工具設定值
     * @var
     */
    protected $translatorLang;

    /**
     * 資料驗證
     * @param $value
     * @param $messageVariables
     * @return mixed
     * @throws \Exception 輸入參數數量不正確
     *
     * Example:
     * protected $messageVariables = ['min'=>PHP_INT_MIN, 'max'=>PHP_INT_MAX];
     * $validBetween->isValid($val, 10, 100);
     */
    public function isValid($value, $messageVariables = [])
    {
        $this->value = $value;
        $this->messages = [];
        if(is_array($messageVariables)) {
            $this->setVariables($messageVariables);
        } elseif(func_num_args() > 1) {
            $messageVariables = func_get_args();
            array_shift($messageVariables);
            if(count($this->messageVariables) != count($messageVariables)){
                throw new \Exception('輸入參數數量不正確');
            }

            foreach ($this->messageVariables as $key=>$variable){
                $this->setVariable($key, array_shift($messageVariables));
            }
        }
        return $this->validate($value);
    }

    /**
     * 驗證內容實做
     * @param $value
     * @return boolean
     */
    abstract protected function validate($value);


    /**
     * 給validate使用產生錯誤訊息
     * @param $templateKey
     * @param null $value
     * @param array $messageVariables
     * @return mixed|null
     */
    public function error($templateKey, $value = null, $messageVariables = [])
    {
        $this->messageVariables = array_merge($this->messageVariables, $messageVariables);
        return $this->createMessage($templateKey, $value);
    }

    /**
     * 產生錯誤訊息
     * @param $templateKey
     * @param string $value
     * @param string $fieldName 欄位名稱
     * @return mixed|null
     */
    protected function createMessage($templateKey, $value = null, $fieldName = null)
    {
        if(!isset($this->messageTemplates[$templateKey])){
            return null;
        }

        if(null === $value){
            $value = $this->value;
        }

        if(null === $fieldName){
            $fieldName = $this->fieldName;
        }

        if (is_object($value)) {
            if (!in_array('__toString', get_class_methods($value))) {
                $value = get_class($value) . ' object';
            } else {
                $value = $value->__toString();
            }
        } elseif (is_array($value)) {
            $value = $this->implodeRecursive($value);
        } else {
            $value = implode((array) $value);
        }

        $from = ['{{value}}'];
        $to = empty($fieldName)? [$value]:[$this->translate($fieldName)];

        foreach ($this->messageVariables as $bindKey=>$variable){
            $from[] = "{{".$bindKey."}}";
            $to[] = $variable;
        }

        $message = str_replace($from, $to, $this->translate($this->getMessageTemplate($templateKey)));
        $this->messages[] = $message;

        return $message;
    }

    /**
     * 預留翻譯工具
     * @param $message
     * @param null $lang
     * @return mixed
     */
    public function translate($message, $lang = null)
    {
        $translator = $this->getTranslator();
        if(null !== $translator){
            return $translator->translate($message, $lang);
        }
        return $message;
    }

    protected function implodeRecursive(array $pieces)
    {
        $values = array();
        foreach ($pieces as $item) {
            if (is_array($item)) {
                $values[] = $this->implodeRecursive($item);
            } else {
                $values[] = $item;
            }
        }

        return implode(', ', $values);
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function getMessageTemplate($templateKey = null)
    {
        if(isset($this->messageTemplates[$templateKey])){
            return $this->messageTemplates[$templateKey];
        }
        $templates = $this->messageTemplates;
        return array_shift($templates);
    }

    public function setMessageTemplate($templateKey, $message)
    {
        $this->messageTemplates[$templateKey] = $message;
        return $this;
    }

    public function getMessageTemplates()
    {
        return $this->messageTemplates;
    }

    public function setMessageTemplates($messageArray)
    {
        foreach ($messageArray as $templateKey => $message){
            $this->setMessageTemplate($templateKey, $message);
        }
        return $this;
    }

    public function getVariables()
    {
        return $this->messageVariables;
    }

    public function getVariable($key)
    {
        if(isset($this->messageVariables[$key])){
            return $this->messageVariables[$key];
        }
        return null;
    }

    public function setVariable($key, $value)
    {
        $this->messageVariables[$key] = $value;
        return $this;
    }

    public function setVariables($variables)
    {
        if(!is_array($variables)) {
            throw new \Exception('messageVariables must be array');
        }
        $this->messageVariables = array_merge($this->messageVariables, $variables);
        return $this;
    }

    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
        return $this;
    }

    public function getFieldName()
    {
        return $this->fieldName;
    }

    public function __get($key)
    {
        return $this->getVariable($key);
    }

    public function __set($key, $value)
    {
        return $this->setVariable($key, $value);
    }

    public function setTranslator($translator)
    {
        $this->translator = $translator;
        return $this;
    }

    public function getTranslator()
    {
        return $this->translator;
    }

    public function setTranslatorLang($lang)
    {
        $this->translatorLang = $lang;
        return $this;
    }

    public function getTranslatorLang()
    {
        return $this->translatorLang;
    }
}
