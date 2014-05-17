<?php
/**
 * 调试
 */
class   BTree_Debug implements BTree_Command {

    use BTree_Search;

    /**
     * 获取实例
     *
     * @param   BTree_Store     $store      存储
     * @param   BTree_Options   $options    配置
     * @return  BTree_Insert                本类实例
     */
    public  static  function getInstance (BTree_Store $store, BTree_Options $options) {

        return  new self($store, $options);
    }

    /**
     * 构造函数
     *
     * @param   BTree_Store     $store      存储
     * @param   BTree_Options   $options    配置
     */
    private function __construct (BTree_Store $store, BTree_Options $options) {

        $this->_store   = $store;
        $this->_options = $options;
    }

    public  function call ($params) {

        $data   = array(
            'pointer_root'  => $this->_store->rootPointer()
        );

        var_dump($data);
    }
}