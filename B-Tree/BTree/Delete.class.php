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

            throw   new Exception('key ' . $key . ' not exists');
        }

        if (BTree_Validate::emptyNode($currentNode)) {

            throw   new Exception('key ' . $key . ' not exists');
        }

        $this->_delete($currentNode, $key);
    }

    /**
     * 从节点中删除关键词
     *
     * @param   BTree_Node  $node   节点
     * @param   string      $key    关键词
     */
    private function _delete (BTree_Node $node, $key) {

        if (!$node->isLeaf()) {

            list($node, $key)   = $this->_moveKeyToLeaf($node, $key);
        }

        $this->_deleteKeyFromLeaf($node, $key);
    }

    /**
     * 将关键词移动到最近的叶节点 (目前是从右侧)
     *
     * @param   BTree_Node  $node   节点
     * @param   string      $key    关键词
     * @return  array               右侧叶节点和右侧的关键词
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

    /**
     * 从叶节点删除关键词
     *
     * @param   BTree_Node  $node   叶节点
     * @param   string      $key    关键词
     * @return  bool                执行结果
     */
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

        return  $this->_deleteMoveNeighbor($node, $key);
    }

    /**
     * 借用临近节点的关键词进行删除操作
     *
     * @param   BTree_Node  $node   节点
     * @param   string      $key    关键词
     * @return  bool                执行结果
     */
    private function _deleteMoveNeighbor (BTree_Node $node, $key) {

        return  $this->_deleteMoveRight($node, $key) || $this->_deleteMoveLeft($node, $key);
    }

    /**
     * 从左叶侧节点借值进行删除
     *
     * @param   BTree_Node  $node   叶节点
     * @param   string      $key    关键词
     * @return  bool                成功返回true|如果邻居节点不存在返回false
     */
    private function _deleteMoveLeft (BTree_Node $node, $key) {

        $parentNode     = $node->parent();
        $neighborLeft   = $this->_neighborLeft($node);

        if (!($neighborLeft instanceof BTree_Node)) {

            return  false;
        }

        $keyParentLeft  = $parentNode->pointerLeftKey($node->pointer());

        if (count($neighborLeft->data()) > $this->_leastNumberKeys()) {

            $valueParentLeft    = $parentNode->match($keyParentLeft);
            $keyLeft            = $neighborLeft->getRightBorderKey();
            $valueLeft          = $neighborLeft->match($keyLeft);
            $node->delete($key, BTree_Node::DELETE_FLAG_RIGHT);
            $node->insert($keyParentLeft, $valueParentLeft, $neighborLeft->rightBorderChild(), $node->leftBorderChild());
            $parentNode->replaceKey($keyParentLeft, $keyLeft, $valueLeft);
            $neighborLeft->delete($keyLeft, BTree_Node::DELETE_FLAG_RIGHT);
            $this->_store->writeNode($node);
            $this->_store->writeNode($parentNode);
            $this->_store->writeNode($neighborLeft);

            return              true;
        }

        $this->_deleteMerge($neighborLeft, $node, $parentNode, $keyParentLeft, $key);

        return          true;
    }

    /**
     * 从右侧叶节点借值进行删除
     *
     * @param   BTree_Node  $node   叶节点
     * @param   string      $key    关键词
     * @return  bool                成功返回true|如果邻居节点不存在返回false
     */
    private function _deleteMoveRight (BTree_Node $node, $key) {

        $parentNode     = $node->parent();
        $neighborRight  = $this->_neighborRight($node);

        if (!($neighborRight instanceof BTree_Node)) {

            return  false;
        }

        $keyParentRight = $parentNode->pointerRightKey($node->pointer());

        if (count($neighborRight->data()) > $this->_leastNumberKeys()) {

            $valueParentRight   = $parentNode->match($keyParentRight);
            $keyRight           = $neighborRight->getLeftBorderKey();
            $valueRight         = $neighborRight->match($keyRight);
            $node->delete($key, BTree_Node::DELETE_FLAG_RIGHT);
            $node->insert($keyParentRight, $valueParentRight, $node->rightBorderChild(), $neighborRight->leftBorderChild());
            $parentNode->replaceKey($keyParentRight, $keyRight, $valueRight);
            $neighborRight->delete($keyRight, BTree_Node::DELETE_FLAG_LEFT);
            $this->_store->writeNode($node);
            $this->_store->writeNode($parentNode);
            $this->_store->writeNode($neighborRight);

            return              true;
        }

        $this->_deleteMerge($node, $neighborRight, $parentNode, $keyParentRight, $key);

        return          true;
    }

    /**
     * 合并删除
     *
     * @param   BTree_Node  $left   左侧节点
     * @param   BTree_Node  $right  右侧节点
     * @param   BTree_Node  $parent 上级节点
     * @param   string      $midKey 相夹关键词
     * @param   string      $key    目标关键词
     */
    private function _deleteMerge (BTree_Node $left, BTree_Node $right, BTree_Node $parent, $midKey, $key) {

        $midValue   = $parent->match($midKey);
        $nextParent = $parent->parent();

        if ($this->_canMergeImmediately($parent, $nextParent)) {

            return  $this->_mergeStore($left, $right, $parent, $nextParent, $midKey, $midValue, $key);
        }

        if ($this->_deleteMoveNeighbor($parent, $midKey)) {

            return  $this->_mergeStore($left, $right, $parent, $nextParent, $midKey, $midValue, $key, true);
        }

        return  false;
    }

    /**
     * 判断是否可以直接合并
     *
     * @param   BTree_Node  $parent     上级节点
     * @param   BTree_Node  $nextParent 上级节点的上级节点
     * @return  bool                    判断结果
     */
    private function _canMergeImmediately (BTree_Node $parent, BTree_Node $nextParent) {

        return  count($parent->data()) > $this->_leastNumberKeys() || $nextParent->isNewRoot();
    }

    /**
     * 合并删除
     *
     * @param   BTree_Node  $left           左侧节点
     * @param   BTree_Node  $right          右侧节点
     * @param   BTree_Node  $parent         上级节点
     * @param   BTree_Node  $nextParent     上级节点的上级节点
     * @param   string      $midKey         相夹关键词
     * @param   string      $midValue       相夹值
     * @param   string      $key            目标关键词
     * @param   bool        $isSaveParent   上级节点是否已保存
     * @return  BTree_Node                  左侧节点
     */
    private function _mergeStore (BTree_Node $left, BTree_Node $right, BTree_Node $parent, BTree_Node $nextParent, $midKey, $midValue, $key, $isSaveParent = false) {

        $left->insert($midKey, $midValue, $left->rightBorderChild(), $right->leftBorderChild());
        $left->merge($right);
        $left->delete($key, BTree_Node::DELETE_FLAG_RIGHT);
        $parent->delete($midKey, BTree_Node::DELETE_FLAG_RIGHT);

        if (!$isSaveParent) {

            $this->_mergeStoreParent($parent, $left, $nextParent);
        }

        $this->_store->writeNode($left);

        return      $left;
    }

    /**
     * 合并保存上级节点
     *
     * @param   BTree_Node  $parent     上级节点
     * @param   BTree_Node  $left       左侧节点
     * @param   BTree_Node  $nextParent 上级节点的上级节点
     */
    private function _mergeStoreParent (BTree_Node $parent, BTree_Node $left, BTree_Node $nextParent) {

        if (count($parent->data()) == 0 && $nextParent->isNewRoot()) {

            $this->_store->rootPointer($left->pointer());
            $this->_store->saveRootPointer();

            return  ;
        }

        $this->_store->writeNode($parent);
    }

    /**
     * 获取右侧邻居节点
     *
     * @param   BTree_Node      $node   当前节点
     * @return  BTree_Node|bool         右侧邻居节点|未找到返回false
     */
    private function _neighborRight (BTree_Node $node) {

        $parentNode     = $node->parent();
        $pointerRight   = $parentNode->pointerRightChild($node->pointer());

        if (BTree_Validate::pointer($pointerRight)) {

            return  $this->_store->readNode($pointerRight, $parentNode);
        }

        return          false;
    }

    /**
     * 获取左侧邻居节点
     *
     * @param   BTree_Node      $node   当前节点
     * @return  BTree_Node|bool         左侧邻居节点|未找到返回false
     */
    private function _neighborLeft (BTree_Node $node) {

        $parentNode     = $node->parent();
        $pointerLeft    = $parentNode->pointerLeftChild($node->pointer());

        if (BTree_Validate::pointer($pointerLeft)) {

            return  $this->_store->readNode($pointerLeft, $parentNode);
        }

        return          false;
    }

    /**
     * 节点所能拥有的最低关键字保有量
     *
     * @return  int 节点最低保有量
     */
    private function _leastNumberKeys () {

        return  ceil($this->_options->numberSlots() / 2) - 1;
    }

    /**
     * 左邻叶节点
     *
     * @param   int         $pointer    指针
     * @param   BTree_Node  $parentNode 上级节点
     * @return  BTree_Node              目标节点
     */
    private function _searchLeftBorderLeaf ($pointer, BTree_Node $parentNode) {

        $currentNode    = $this->_store->readNode($pointer, $parentNode);

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

        $currentNode    = $this->_store->readNode($pointer, $parentNode);

        if ($currentNode->isLeaf()) {

            return  $currentNode;
        }

        return          $this->_searchRightBorderLeaf($currentNode->rightBorderChild(), $currentNode);
    }
}