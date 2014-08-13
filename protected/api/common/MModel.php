<?php
/**
 * Miniyun 模型基类
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */
abstract class MModel 
    extends MComponent 
    implements IteratorAggregate
{
    //
    // 错误数组
    //
    private $_errors = array();
    
    
    /**
     * 判断模型是否拥有指定的错误信息
     * @param string $attribute 属性名称，空表明获取所有的属性
     * @return boolean 判断是否拥有指定属性的错误
     */
    public function hasErrors($attribute = null)
    {
        if ($attribute === null)
            return $this->_errors !== array();
        else
            return isset($this->_errors[$attribute]);
    }

    /**
     * 获取指定属性的错误消息或者所有的错误信息
     * @param string $attribute 属性名称，空表明获取所有的属性
     * @return array 对应的错误数组
     */
    public function getErrors($attribute = null)
    {
        if ($attribute === null)
            return $this->_errors;
        
        if (isset($this->_errors[$attribute]))
            return $this->_errors[$attribute]; 

        return array();
    }

    /**
     * 返回指定属性的错误信息
     * 
     * @param string $attribute 属性名称
     * @return string 对应属性的错误信息.
     * 
     */
    public function getError($attribute)
    {
        if (isset($this->_errors[$attribute]))
            return $this->_errors[$attribute]; 

        return null;
    }

    /**
     * 添加指定错误到对应的属性中
     * @param string $attribute 属性名称
     * @param string $error 错误信息
     */
    public function addError($attribute, $error)
    {
        $this->_errors[$attribute][] = $error;
    }

    /**
     * 添加错误列表信息
     * @param array $errors 错误列表数组
     * 
     */
    public function addErrors($errors)
    {
        foreach ($errors as $attribute=>$error)
        {
            if (is_array($error))
            {
                foreach ($error as $e)
                    $this->_errors[$attribute][] = $e;
            }
            else
                $this->_errors[$attribute][] = $error;
        }
    }

    /**
     * 取消指定属性的错误信息
     * 
     * @param string $attribute 属性名称，空表明获取所有的属性
     */
    public function clearErrors($attribute = null)
    {
        if ($attribute === null)
            $this->_errors = array();
        else
            unset($this->_errors[$attribute]);
    }
    
    /**
     * 返回一个可以迭代的属性列表
     * 执行IteratorAggregate接口对应的必须函数
     * @return 返回一个可以迭代的属性列表
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_m);
    }

    /**
     * 返回一个json字符串
     * @return 解析成功返回对应的json字符串，否则返回false
     */
    public function toJson()
    {
        if (is_array($this->_m) === false ||
            count($this->_m) <= 0)
        {
            return false;
        }
        
        $json = json_encode($this->_m);
        return $json;
    }
}