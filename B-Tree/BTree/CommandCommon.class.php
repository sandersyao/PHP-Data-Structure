<?php
/**
 * 命令公共成员
 */
trait BTree_CommandCommon {

    /**
     * 存储
     */
    protected   $_store;

    /**
     * 配置数据
     */
    protected   $_options;

    /**
     * 获取存储实例
     *
     * @return  BTree_Store 存储实例
     */
    protected   function _store () {

        return  $this->_store;
    }

    /**
     * 获取配置实例
     *
     * @return  BTree_Options   配置实例
     */
    protected   function _options () {

        return  $this->_options;
    }
}