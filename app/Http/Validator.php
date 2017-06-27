<?php

namespace App\Http;

use Countable;
use SplFileInfo;
use DateTime;

/**
 * 檢核器
 *
 * @package App\Http
 */
class Validator
{
    /**
     * 區間或大小的規則
     *
     * @var array
     */
    protected $sizeRules = [
        'Size',
        'Min',
        'Max'
    ];

    /**
     * 型態或行為規則
     *
     * @var array
     */
    protected $typeRules = [
        'required',
        'int',
        'string',
        'bool',
        'array',
        'numeric',
        'date',
        'email',
        'regex'
    ];

    /**
     * 要檢核的值
     *
     * @var array
     */
    private $data;

    /**
     * 執行哪些規則
     *
     * @var array
     */
    private $rules;

    /**
     * 統合錯誤訊息
     *
     * @var array
     */
    private $errorMessage = [];

    /**
     * constructor
     *
     * @param  array $data
     * @param  array $rule
     */
    public function __construct(array $data = [], array $rule = [])
    {
        $this->data = $data;
        $this->rules = $rule;
    }

    /**
     * 注入測試值及規則
     *
     * @param  array $data
     * @param  array $rule
     * @return \App\Http\Validator
     */
    public function make(array $data, array $rule)
    {
        $this->data = $data;
        $this->rules = $rule;
        $this->errorMessage = [];
        return $this;
    }

    /**
     * 逐筆規則執行檢核
     *
     * @param  string $attribute
     * @param  string $rule
     * @return void
     */
    protected function doValidate($attribute, $rule)
    {
        list($rule, $parameter) = $this->parseRule($rule);
        $value = $this->getTargetValue($attribute);

        $rule = ucfirst($rule);
        $method = "validate{$rule}";
        if (!method_exists($this, $method) or !$this->$method($value, $parameter)) {
            $this->errorMessage[$attribute] = "`{$attribute}` validate {$rule} is not pass";
        }
    }

    /**
     * 取得檢核對象的實際值
     *
     * @param  string $attribute
     * @return mixed
     */
    protected function getTargetValue($attribute)
    {
        if (strpos($attribute, '.')) {
            return $this->getSplitValue($attribute);
        } else {
            return (isset($this->data[$attribute])) ? $this->data[$attribute] : null;
        }
    }

    /**
     * 開始跑檢核，確認是否通過
     *
     * @return bool
     */
    public function isPass()
    {
        foreach ($this->rules as $attribute => $rules) {
            if (!is_array($rules)) {
                $rules = [$rules];
            }
            foreach ($rules as $rule) {
                $this->doValidate($attribute, $rule);

            }
        }
        return count($this->errorMessage) === 0;
    }

    /**
     * 解析規則細項
     *
     * @param  string $rule
     * @return array
     */
    protected function parseRule($rule)
    {
        if (strpos($rule, ':') !== false) {
            return explode(':', $rule, 2);
        }
        return [
            $rule,
            []
        ];
    }

    /**
     * 取得檢核錯誤訊息
     *
     * @return array
     */
    public function getErrorMessage()
    {
        $message = '';

        foreach ($this->errorMessage as $content) {
            $message = $message . $content . PHP_EOL;
        }
        return $message;
    }

    /**
     * 取得檢核對象底下某key值
     *
     * @param  string $target
     * @return array
     */
    private function getSplitValue($target)
    {
        $keyMap = explode('.', $target);
        return $this->parseData($this->data, $keyMap);
    }

    /**
     * 解析array tree 值
     *
     * @param  mixed $data
     * @param  array $keyMap
     * @return mixed
     */
    public function parseData($data, $keyMap)
    {
        $nextData = current($keyMap);
        if (isset($data[$nextData])) {
            if (next($keyMap)) {
                return $this->parseData($data[$nextData], $keyMap);
            }
            return $data[$nextData];
        } else {
            return null;
        }
    }

    /**
     * 檢核是否含值
     *
     * @param  mixed $value
     * @return bool
     */
    protected function validateRequired($value)
    {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value) && trim($value) === '') {
            return false;
        } elseif ((is_array($value) || $value instanceof Countable) && count($value) < 1) {
            return false;
        } elseif ($value instanceof SplFileInfo) {
            return (string) $value->getPath() != '';
        }

        return true;
    }

    /**
     * 檢核是否為整數
     *
     * @param  mixed $value
     * @return bool
     */
    protected function validateInt($value)
    {
        return is_null($value) || filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * 檢核是否為數值
     *
     * @param  mixed $value
     * @return bool
     */
    protected function validateNumeric($value)
    {
        return is_null($value) || is_numeric($value);
    }

    /**
     * 檢核是否為字串
     *
     * @param  mixed $value
     * @return bool
     */
    protected function validateString($value)
    {
        return is_null($value) || is_string($value);
    }

    /**
     * 檢核是否為陣列
     *
     * @param  mixed $value
     * @return bool
     */
    protected function validateArray($value)
    {
        return is_null($value) || is_array($value);
    }

    /**
     * 檢核是否為布林值
     *
     * @param  mixed $value
     * @return bool
     */
    protected function validateBool($value)
    {
        $acceptable = [
            true,
            false,
            0,
            1,
            '0',
            '1'
        ];

        return is_null($value) || in_array($value, $acceptable, true) || filter_var($value, FILTER_VALIDATE_BOOLEAN) !== false;
    }

    /**
     * 檢核大小是否一致
     *
     * @param  mixed $value
     * @param  int $parameter
     * @return bool
     */
    protected function validateSize($value, $parameter)
    {
        return $this->getSize($value) == $parameter;
    }

    /**
     * 檢核最小值
     *
     * @param  mixed $value
     * @param  int $parameter
     * @return bool
     */
    protected function validateMin($value, $parameter)
    {
        return $this->getSize($value) >= $parameter;
    }

    /**
     * 檢核最大值
     *
     * @param  mixed $value
     * @param  int $parameter
     * @return bool
     */
    protected function validateMax($value, $parameter)
    {
        return $this->getSize($value) <= $parameter;
    }

    /**
     * 檢核是否符合日期
     *
     * @param  mixed $value
     * @return bool
     */
    protected function validateDate($value)
    {
        if ($value instanceof DateTime) {
            return true;
        }

        if ((!is_string($value) && !is_numeric($value)) || strtotime($value) === false) {
            return false;
        }

        $date = date_parse($value);

        return checkdate($date['month'], $date['day'], $date['year']);
    }

    /**
     * 檢核是否符合email格式
     *
     * @param  mixed $value
     * @return bool
     */
    protected function validateEmail($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * 檢核是否符合正則式
     *
     * @param  mixed   $value
     * @param  array   $parameter
     * @return bool
     */
    protected function validateRegex($value, $parameter)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }
        return preg_match($parameter, $value) > 0;
    }

    /**
     * 取得實際值大小
     *
     * @param  mixed $value
     * @return mixed
     */
    protected function getSize($value)
    {
        if (is_numeric($value) || is_int($value)) {
            return $value;
        } elseif (is_array($value)) {
            return count($value);
        } elseif ($value instanceof SplFileInfo) {
            // 單位kb
            return $value->getSize() / 1024;
        }

        return mb_strlen($value);
    }
}