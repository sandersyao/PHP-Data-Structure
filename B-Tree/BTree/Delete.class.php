<?php
/**
 * B-Tree删除
 */
class   BTree_Delete
    implements BTree_Command {

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

        $key            = $params['key'];
        $currentNode    = $this->_searchNode($key);

        if (!BTree_Validate::value($currentNode->match($key))) {

            throw   new Exception('key not exists');
        }

        if (BTree_Validate::emptyNode($currentNode)) {

            throw   new Exception('key not exists');
        }

        if (!$currentNode->isLeaf()) {

            list($currentNode, $key)    = $this->_moveKeyToLeaf($currentNode, $key);
        }

        $this->_deleteKeyFromLeaf($currentNode, $key);
    }

    /**
     * 将关键词移动到最近的叶节点
     */
    private function _moveKeyToLeaf (BTree_Node $node, $key) {

        $value      = $node->match($key);
        $leafRight  = $this->_searchLeftBorderLeaf($node->keyRightChild($key), $node);
        $keyRight   = $leafRight->getLeftBorderKey();
        $valueRight = $leafRight->match($keyRight);
        $node->replaceKey($key, $keyRight, $valueRight);
        $this->_store->writeNode($node);

        return      array($leafRight, $keyRight);
    }

    private function _deleteKeyFromLeaf (BTree_Node $node, $key) {

        $parentNode     = $node->parent();

        if (
            count($node->data()) > $this->_leastNumberKeys() ||
            !($parentNode instanceof BTree_Node) ||
            $parentNode->isNewRoot()
        ) {

            $node->delete($key);
            $this->_store->writeNode($node);

            return  true;
        }

        if ($this->_deleteMoveRight($node, $key) || $this->_deleteMoveLeft($node, $key)) {

            return  true;
        }

        return  false;
    }

    private function _deleteMoveLeft (BTree_Node $node, $key) {

        $parentNode         = $node->parent();
        $neghborLeft        = $this->_neighborLeft($node);

        if (!($neghborLeft instanceof BTree_Node)) {

            return  false;
        }

        $keyParentLeft      = $parentNode->pointerLeftKey($node->pointer());

        if (count($neghborLeft->data()) > $this->_leastNumberKeys()) {

            $valueParentLeft    = $parentNode->match($keyParentLeft);
            $keyLeft            = $neghborRight->getRightBorderKey();
            $valueLeft          = $neghborRight->match($keyRight);
            $node->delete($key);
            $node->insert($keyParentLeft, $valueParentLeft);
            $parentNode->replaceKey($keyParentLeft, $keyLeft, $valueLeft);
            $neghborRight->delete($keyLeft);
            $this->_store->writeNode($node);
            $this->_store->writeNode($parentNode);
            $this->_store->writeNode($neghborLeft);

            return              true;
        }

        $this->_deleteMerge($keyParentLeft, $node, $parentNode, $keyParentLeft);

        return          true;
    }

    private function _deleteMoveRight (BTree_Node $node, $key) {

        $parentNode         = $node->parent();
        $neghborRight       = $this->_neighborRight($node);

        if (!($neghborRight instanceof BTree_Node)) {

            return  false;
        }

        $keyParentRight     = $parentNode->pointerRightKey($node->pointer());

        if (count($neghborRight->data()) > $this->_leastNumberKeys()) {

            $valueParentRight   = $parentNode->match($keyParentRight);
            $keyRight           = $neghborRight->getLeftBorderKey();
            $valueRight         = $neghborRight->match($keyRight);
            $node->delete($key);
            $node->insert($keyParentRight, $valueParentRight);
            $parentNode->replaceKey($keyParentRight, $keyRight, $valueRight);
            $neghborRight->delete($keyRight);
            $this->_store->writeNode($node);
            $this->_store->writeNode($parentNode);
            $this->_store->writeNode($neghborRight);

            return              true;
        }

        $this->_deleteMerge($node, $neghborRight, $parentNode, $keyParentRight, $key);

        return          true;
    }

    private function _deleteMerge (BTree_Node $left, BTree_Node $right, BTree_Node $parent, $midKey, $key) {

        $midValue   = $parent->match($midKey);
        $nextParent = $parent->parent();
        $left->insert($midKey, $midValue);
        $left->merge($right);
        $parent->delete($midKey, false);
        $left->delete($key);

        if (count($parent->data()) > 0) {

            $this->_store->writeNode($parent);
        } else {

            $this->_store->rootPointer($left->pointer());
            $this->_store->saveRootPointer();
        }

        if (
            count($parent->data()) <= $this->_leastNumberKeys() &&
            $nextParent instanceof BTree_Node &&
            !$nextParent->isNewRoot()
        ) {

            $neghborRight   = $this->_neighborRight($parent);

            if ($neghborRight instanceof BTree_Node) {

                $nextRight  = $neghborRight;
                $nextLeft   = $parent;
                $nextMidKey = $nextParent->pointerRightKey($parent->pointer());
            }

            $neghborLeft    = $this->_neighborLeft($parent);

            if ($neghborLeft instanceof BTree_Node) {

                $nextRight  = $parent;
                $nextLeft   = $neghborLeft;
                $nextMidKey = $nextParent->pointerRightKey($neghborLeft->pointer());
            }

            $this->_deleteMerge($nextLeft, $nextRight, $nextParent, $nextMidKey, $midKey);
        }

        $this->_store->writeNode($left);

        return      $left;
    }

    private function _neighborRight (BTree_Node $node) {

        $parentNode     = $node->parent();
        $pointerRight   = $parentNode->pointerRightChild($node->pointer());

        if ($this->_validatePointer($pointerRight)) {

            return  $this->_store->readNode($pointerRight);
        }

        return          false;
    }

    private function _neighborLeft (BTree_Node $node) {

        $parentNode     = $node->parent();
        $pointerLeft    = $parentNode->pointerLeftChild($node->pointer());

        if ($this->_validatePointer($pointerLeft)) {

            return  $this->_store->readNode($pointerLeft);
        }

        return          false;
    }

    /**
     * 节点所能拥有的最低关键字保有量
     */
    private function _leastNumberKeys () {

        return  ceil($this->_options->numberSlots() / 2) - 1;
    }

    private function _searchLeftBorderLeaf ($pointer, $parentNode) {

        $currentNode        = $this->_store->readNode($pointer, $parentNode);

        if ($currentNode->isLeaf()) {

            return  $currentNode;
        }

        list($leftPointer)  = $currentNode->children();

        return              $this->_searchLeftBorderLeaf($leftPointer, $currentNode);
    }
}