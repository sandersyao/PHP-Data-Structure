<?php
/**
 * 验证逻辑
 */
class   BTree_Validate {

    /**
     * 验证值
     */
    public  static  function value ($value) {

        return  is_int($value) && $value > 0;
    }

    /**
     * 验证指针
     */
    public  static  function pointer ($pointer) {

        return  is_int($pointer) && $pointer > 0;
    }

    /**
     * 验证关键词
     */
    public  static  function key ($key) {

        return  '' !== $key;
    }

    /**
     * 校验空节点
     */
    public  static  function emptyNode (BTree_Node $node) {

        return  empty($node->data());
    }
}