<?php
/**
 * B-Tree
 *
 * PHP Version > 5.4.0
 */
class   BTree {

    /**
     * 存储
     */
    private $_store;

    /**
     * 配置
     */
    private $_options;

    /**
     * 启用一个索引
     *
     * @param   string  $file           文件路径
     * @param   array   $optionsData    配置数据
     */
    public  static  function open ($file, $optionsData = array()) {

        $options    = BTree_Options::create($optionsData);
        $store      = BTree_Store::open($file, $options);

        return  new self($store, $options);
    }

    /**
     * 构造函数
     *
     * @param   BTree_Store     $store      存储
     * @param   BTree_Options   $options    配置
     */
    private function __construct ($store, $options) {

        $this->_store   = $store;
        $this->_options = $options;
    }

    /**
     * 析构函数
     */
    public  function __destruct () {

        $this->_store   = NULL;
    }

    /**
     * 执行命令
     *
     * @param   string  $name   命令
     * @param   array   $params 命令参数
     * @return  mixed           命令执行结果
     */
    public  function command ($name, $params) {

        return  $this->_getCommand($name)->call($params);
    }

    /**
     * 获取命令实例
     *
     * @param   string          $name   命令
     * @return  BTree_Command           命令逻辑实例
     */
    private function _getCommand ($name) {

        $class              = $this->_getCommandClass($name);
        $instanceCallback   = array($class, 'getInstance');

        if (!class_exists($class) || !is_callable($instanceCallback)) {

            throw   new Exception('command invalid.');
        }

        $instance           = call_user_func($instanceCallback, $this->_store, $this->_options);

        if (!($instance instanceof BTree_Command)) {

            throw   new Exception('command invalid.');
        }

        return              $instance;
    }

    /**
     * 获取命令类名
     *
     * @param   string  $name   命令
     * @return  string          类名
     */
    private function _getCommandClass ($name) {

        $prefix = __CLASS__ . '_';

        return $prefix . ucfirst(strtolower($name));
    }
}