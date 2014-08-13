<?php
/**
 * Miniyun 所有模块都需要使用到的基类，利用__get、__set、__call等特殊重载函数来实现类或者
 * 属性、方法的延后加载，提高整体的性能
 * @author app <app@miniyun.cn>
 * @link http://www.miniyun.cn
 * @copyright 2014 Chengdu MiniYun Technology Co. Ltd.
 * @license http://www.miniyun.cn/license.html 
 * @since 1.6
 */

class MComponent
{
    public $_m = array();

    /**
     * 获取用户的属性或者方法
     * 
     * 重载php的特殊函数来获取特定的属性，达到动态获取的目的。不能够直接调用此函数
     * @param string $name 属性名字或者事件名字
     * @return 混合的属性属性或者事件的值
     * @throws Exception 如果属性或者事件没有设置，则抛出对应的异常
     * @see __set
     */
    public function __get($name)
    {
        // 获取属性函数优先
        $get_func = 'get'.$name;
        if (method_exists($this, $get_func))
        {
            return $this->$get_func();
        }
        
        // 判断是否设置指定的关键字
        else if (isset($this->_m[$name]))
        {
            return $this->_m[$name];
        }

        // 成员变量
        else if (isset($this->$name))
        {
            return $this->$name;
        }

        // 迭代所有子对象，判断是否拥有对应的属性
        else if (is_array($this->_m))
        {
            foreach($this->_m as $object)
            {
                if (property_exists($object,$name) || $object->canGetProperty($name))
                    return $object->$name;
            }
        }

        throw new Exception(Yii::t('api','Property "{class}.{property}" is not defined.',
            array('{class}'=>get_class($this), '{property}'=>$name)));
    }

    /**
     * 设置指定属性的值
     * 
     * 重载php的特殊函数来设置特定的属性，达到动态设置的目的。不能够直接调用此函数
     *
     * @param string $name 需要设置属性的名称
     * @param mixed $value 需要设置属性的值
     * @return mixed
     * @throws Exception 如果对象属性只读或者失败，则抛出异常.
     * @see __get
     */
    public function __set($name, $value)
    {
        // 优先处理设置函数
        $set_func = 'set'.$name;
        if (method_exists($this, $set_func))
        {
            return $this->$set_func($value);
        }

        // 判断是否在数组中进行设置
        $this->_m[$name] = $value;
    }

    /**
     * 判断属性是否设置
     * 
     * @param string $name 需要判断的属性名称
     * @return boolean 有对应的属性返回true，否则返回false
     * 
     */
    public function __isset($name)
    {
        $get_func = 'get'.$name;
        if (method_exists($this, $get_func))
            return $this->$get_func() !== null;

        else if(is_array($this->_m))
        {
            if (isset($this->_m[$name]))
                return true;
        }
        return false;
    }

    /**
     * 取消指定名称的变量或者属性
     * 
     * @param string $name 需要取消属性的名称
     * @throws Exception 如果属性不存在或者只读，则抛出异常.
     * @return mixed
     */
    public function __unset($name)
    {
        $set_fun ='set'.$name;
        if (method_exists($this, $set_fun))
            $this->$set_fun(null);

        else if(is_array($this->_m))
        {
            if (isset($this->_m[$name]))
                $this->_m[$name] = null;
            else
            {
                foreach($this->_m as $object)
                {
                    if (property_exists($object,$name))
                        return $object->$name = null;
                    else if ($object->canSetProperty($name))
                        return $object->$setter(null);
                }
            }
        }
        
        else if (method_exists($this, 'get'.$name))
            throw new Exception(Yii::t('api','Property "{class}.{property}" is read only.',
                array('{class}'=>get_class($this), '{property}'=>$name)));
    }

    /**
     * 重载__call函数调用指定名称的函数
     * 
     * @param string $name 方法名称
     * @param array $parameters 方法参数
     * @return mixed 方法返回值
     * 
     */
    public function __call($name, $parameters)
    {
        if ($this->_m !== null)
        {
            foreach($this->_m as $object)
            {
                if (method_exists($object, $name))
                    return call_user_func_array(
                        array($object, $name),
                        $parameters
                        );
            }
        }
        
        if (class_exists('Closure', false) && 
            $this->canGetProperty($name) && 
            $this->$name instanceof Closure)
        {
            return call_user_func_array($this->$name, $parameters);
        }

        throw new Exception(Yii::t('api','{class} and its behaviors do not have a method or closure named "{name}".',
            array('{class}'=>get_class($this), '{name}'=>$name)));
    }

    /**
     * 判断是否定义了对应的属性
     * 
     * @param string $name 属性名称
     * @return boolean 拥有对应的属性则返回true，否则返回false
     * @see canGetProperty
     * @see canSetProperty
     */
    public function hasProperty($name)
    {
        return method_exists($this, 'get'.$name) || method_exists($this, 'set'.$name);
    }

    /**
     * 判断是否用于一个属性的读权限
     * 
     * @param string $name 属性名称
     * @return boolean 属性是否拥有可读权限
     * @see canSetProperty
     */
    public function canGetProperty($name)
    {
        return method_exists($this, 'get'.$name);
    }

    /**
     * 判断指定属性是否可以进行写操作
     * 
     * @param string $name 属性名称
     * @return boolean 属性是否拥有可写权限
     * @see canGetProperty
     */
    public function canSetProperty($name)
    {
        return method_exists($this,'set'.$name);
    }
}