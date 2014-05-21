<?php
/**
 * 遍历所需逻辑
 */
trait   BTree_Traversal {

    /**
     * 左邻叶节点
     *
     * @param   int         $pointer    指针
     * @param   BTree_Node  $parentNode 上级节点
     * @return  BTree_Node              目标节点
     */
    private function _searchLeftBorderLeaf ($pointer, BTree_Node $parentNode) {

        $currentNode    = $this->_store()->readNode($pointer, $parentNode);

        if ($currentNode->isLeaf()) {

            return  $currentNode;
        }

        return          $this->_searchLeftBorderLeaf($currentNode->leftBorderChild(), $currentNode);
    }

    /**
     * 右邻叶节点
     *
     * @param   int         $pointer    指针
     * @param   BTree_Node  $parentNode 上级节点
     * @return  BTree_Node              目标节点
     */
    private function _searchRightBorderLeaf ($pointer, $parentNode) {

        $currentNode    = $this->_store()->readNode($pointer, $parentNode);

        if ($currentNode->isLeaf()) {

            return  $currentNode;
        }

        return          $this->_searchRightBorderLeaf($currentNode->rightBorderChild(), $currentNode);
    }

    /**
     * 获取存储实例
     *
     * @return  BTree_Store 存储实例
     */
    abstract    protected   function _store ();
}