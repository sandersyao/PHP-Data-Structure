<?php
/**
 * 公共逻辑
 */
trait BTree_Search {


    /**
     * 匹配到节点
     * 两种情况： 找到返回节点 未找到 返回叶子节点
     *
     * @param   string          $key        关键词
     * @param   int|null        $pointer    指针
     * @param   BTree_Node|null $parentNode 上级节点
     * @return  BTree_Node                  节点
     */
    protected   function _searchNode ($key, $pointer = NULL, $parentNode = NULL) {

        if (NULL == $pointer) {

            $pointer    = $this->_store()->rootPointer();
            $parentNode = NULL;
        }

        $currentNode    = $this->_store()->readNode($pointer, $parentNode);
        $value          = $currentNode->match($key);

        if (BTree_Validate::value($value)) {

            return  $currentNode;
        }

        $childPointer   = $currentNode->matchChildren($key);

        if (!BTree_Validate::pointer($childPointer)) {

            return  $currentNode;
        }

        return  $this->_searchNode($key, $childPointer, $currentNode);
    }

    /**
     * 获取存储实例
     *
     * @return  BTree_Store 存储实例
     */
    abstract    protected   function _store ();
}