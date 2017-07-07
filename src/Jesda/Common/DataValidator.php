<?php
/**
 * @author mei
 * @date 2017/6/19
 * @since 2017/6/19 依據精靈設定檔整合驗證資料
 */

namespace Jesda\Common;

use Jesda\Validate\Email;
use Jesda\Validate\IdentityNumber;
use Jesda\Validate\NotEmpty;
use Jesda\Validate\ValidateDate;
use Jesda\Validate\ValidateFloat;
use Jesda\Validate\ValidateInt;

class DataValidator
{
    /**
     * 讀入的設定檔
     * @var array
     */
    protected $config;

    /**
     * 精靈設定檔名稱
     * @var string
     */
    protected $modelName;

    protected $options;

    /**
     * 讀取精靈設定尋找路徑
     * @var array
     */
    protected $path = [];

    /**
     * 收集錯誤訊息
     * @var array
     */
    protected $errorMessages = [];

    /**
     * 錯誤的欄位
     * @var array
     */
    protected $errorFields = ["statusCode" => 500];

    protected $translator;

    /**
     * DataValidator constructor.
     * @param string $modelName 設定模組
     */
    public function __construct($modelName)
    {
        $this->addPath(PROJECT_PATH.'/configs/models');
        $this->setModelName($modelName);
    }

    /**
     * @param \ArrayObject $data
     * @param null $group
     * @return bool
     */
    public function validate(\ArrayObject $data, $group = null)
    {
        $config = $this->getConfig();
        $isGroup = empty($group) ? false:true;

        if(!isset($config['validate'])){
            return true;
        }

        # 過濾不存在validate設定內欄位
        foreach($data as $key => $value)
        {
            if( !isset($config['validate'][$key]) ){
                unset ($data[$key]);
            }
        }

        foreach ($config['validate'] as $fieldName => $fieldConfig){

            $groups = explode(',', str_replace(' ', '', $fieldConfig['group']));

            # 過濾非指定group的欄位
            if( $isGroup && !in_array($group, $groups) ){
                unset ($data[$fieldName]);
                continue;
            }

            # 是否包含在來源資料中
            $isExist = array_key_exists($fieldName, $data);
            # 是否必填
            $isRequired = ($fieldConfig['required'] == 1) ? true:false;
            # 不存在來源資料中，且非必填，不檢查
            if(!$isExist && !$isRequired){
                continue;
            }

            $value = $isExist ? $data[$fieldName]:null;
            $caption = $fieldConfig['caption'];

            # 先檢查空值，若必填空值新增空值訊息停止檢查、非必填空值直接通過
            $validateNotEmpty = new NotEmpty();
            switch ($fieldConfig['type']) {
                case 'integer':
                    $validateNotEmpty->setType(NotEmpty::INTEGER + NotEmpty::NULL);
                    break;
                case 'float':
                    $validateNotEmpty->setType(NotEmpty::FLOAT + NotEmpty::NULL);
                    break;
                default:
                    $validateNotEmpty->setType(NotEmpty::STRING + NotEmpty::NULL);
            }
            $isNotEmpty = $validateNotEmpty->setFieldName($caption)->isValid($value);
            if(!$isNotEmpty){
                if($isRequired) {
                    $this->addErrorMessage($validateNotEmpty->getMessages());
                    $this->addErrorFields("{$fieldName}_CanNotBeEmpty");
                }
                continue;
            }

            # 檢查Type
            $validateType = null;
            switch ($fieldConfig['type']) {
                // 檢查整數
                case 'integer':
                    $validateType = new ValidateInt();
                    break;
                // 檢查浮點數
                case 'float':
                    $validateType = new ValidateFloat();
                    break;
                // 檢查日期格式
                case 'date':
                    $validateType = new ValidateDate();
                    break;
                // 檢查Email
                case 'email':
                    $validateType = new Email();
                    break;
                // 檢查身分證號(台灣)
                case 'id':
                    $validateType = new IdentityNumber();
                    break;
            }

            if(null !== $validateType){
                $result = $validateType->setFieldName($caption)->isValid($value);
                if(!$result){
                    $this->addErrorMessage($validateType->getMessages());
                    $this->addErrorFields("{$fieldName}_IsInvalid");
                    continue;
                }
            }

            # 自訂檢查項目
            if(isset($fieldConfig['obj']) && is_array($fieldConfig['obj'])){
                foreach ($fieldConfig['obj'] as $validateClass => $option){
                    $params = [];
                    if(isset($option['params']) && !empty($option['params'])){
                        $params = explode(',', $option['params']);
                    }
                    array_unshift($params, $value);

                    /** @var \Jesda\Validate\BaseValidate $validate */
                    $validate = new $validateClass();
                    if(isset($option['messages']) && is_array($option['messages'])){
                        $validate->setMessageTemplates($option['messages']);
                    }
                    $validate->setFieldName($caption);
                    $result = call_user_func_array([$validate, 'isValid'], $params);

                    if(!$result){
                        $this->addErrorMessage($validate->getMessages());
                        $this->addErrorFields("{$fieldName}_IsInvalid");
                        break;
                    }
                }
            }
        }

        if(count($this->errorMessages) > 0){
            return false;
        }

        return true;
    }

    public function getErrorFields()
    {
        return $this->errorFields;
    }
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    public function addErrorFields($fields)
    {
        if(is_array($fields)){
            foreach ($fields as $field){
                $this->errorFields[] = $field;
            }
        } elseif(is_string($fields)) {
            $this->errorFields[] = $fields;
        }

        return $this;
    }

    public function addErrorMessage($messages)
    {
        if(is_array($messages)){
            foreach ($messages as $message){
                $this->errorMessages[] = $message;
            }
        } elseif(is_string($messages)) {
            $this->errorMessages[] = $messages;
        }

        return $this;
    }

    public function getModelName()
    {
        return $this->modelName;
    }

    public function setModelName($modelName)
    {
        $this->modelName = $modelName;
        $this->getConfig(true);
        return $this;
    }

    public function getConfig($isUpdate = false)
    {
        if($isUpdate || !$this->config){
            $modelName = $this->getModelName();

            $paths = $this->getPath();
            if(!is_array($paths)){
                $paths = [$paths];
            }

            $isFound = false;
            foreach ($paths as $path){
                $filePath = $path.DIRECTORY_SEPARATOR.$modelName.'.json';
                if(is_file($filePath)){
                    $json = file_get_contents($filePath);
                    $this->setConfig(json_decode($json, true));
                    $isFound = true;
                    break;
                }
            }

            if(!$isFound){
                throw new \Exception(sprintf('Config "%s" not found', $modelName));
            }
        }

        return $this->config;
    }

    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setPath($path)
    {
        if(is_array($path)) {
            $this->path = $path;
        } elseif(is_string($path)) {
            $this->path = [$path];
        } else {
            throw new \Exception('expected string or array');
        }
        return $this;
    }

    public function addPath($path)
    {
        $this->path[] = $path;
        return $this;
    }

    public function getOption($key)
    {
        return (isset($this->options[$key]))? $this->options[$key]:null;
    }

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }
}