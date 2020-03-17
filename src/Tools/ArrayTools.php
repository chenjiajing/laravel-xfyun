<?php


namespace ChenJiaJing\XunFeiYun\Tools;

use ArrayAccess;

class ArrayTools implements ArrayAccess
{
    private $config = [];

    /**当前配置项
     * ArrayTools constructor.
     * @param $options
     */
    public function __construct(array  $options)
    {
        $this->config = $options;
    }

    /**设置配置项值
     * @param $offset
     * @param $value
     */
    public function set($offset, $value)
    {
        $this->offsetSet($offset, $value);
    }

    /**获取配置项值
     * @return array|mixed|null
     */
    public function get($offset = null)
    {
        return $this->offsetGet($offset);
    }

    /** 判断配置项key 是否存在
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
        return isset($this->config[$offset]);
    }

    /** 获取配置项值
     * @param null $offset
     * @return array|mixed|null
     */
    public function offsetGet($offset = null)
    {
        // TODO: Implement offsetGet() method.
        if(is_null($offset)){
            return $this->config;
        }
        return isset($this->config[$offset])?$this->config[$offset]:null;
    }

    /** 设置配置项值
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
        if(is_null($offset)){
            $this->config[] = $value;
        }else{
            $this->config[$offset] = $value;
        }

    }

    /** 清理配置项
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
        if(is_null($offset)){
            unset($this->config);
        }else{
            unset($this->config[$offset]);
        }
    }

}
