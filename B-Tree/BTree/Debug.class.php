<?php
/**
 * 调试
 */
class   BTree_Debug implements BTree_Command {

    use BTree_CommandCommon;

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

    /**
     * 调用本命令逻辑
     *
     * @param   array   $params 命令参数
     */
    public  function call ($params) {

        $rootPointer    = $this->_store()->rootPointer();
        $pointer        = isset($params['pointer']) && $params['pointer'] > 0
                        ? $params['pointer']
                        : $rootPointer;
        $data           = array(
            'pointer_root'  => $rootPointer,
            'target'        => $this->_store()->readNode($pointer),
        );

        var_dump($data);
    }
}