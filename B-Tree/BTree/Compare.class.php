<?php
/**
 * 比较查询逻辑
 */
final   class   BTree_Compare implements
    BTree_Command {

    use BTree_Search,
        BTree_CommandCommon;

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
     * 克隆
     */
    private function __clone () {
    }

    /**
     * 执行
     *
     * @param   $params 参数
     */
    public  function call ($params) {

        $enumOperator   = array(BTree_Iterator::OPERATOR_GREATER_THAN, BTree_Iterator::OPERATOR_LESS_THAN);
        $key            = $params['key'];
        $operator       = isset($params['operator']) && in_array($params['operator'], $enumOperator)
                        ? $params['operator']
                        : BTree_Iterator::OPERATOR_GREATER_THAN;
        $node           = $this->_searchNode($key);

        if (!($node instanceof BTree_Node)) {

            return  false;
        }

        return  new BTree_Iterator($this->_store(), $node, $key, $operator);
    }
}