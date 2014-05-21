<?php
/**
 * B-Tree迭代器
 */
final   class   BTree_Iterator implements
    Iterator {

    use BTree_Traversal;

    /**
     * 操作符 大于
     */
    const   OPERATOR_GREATER_THAN   = '>';

    /**
     * 操作符 小于
     */
    const   OPERATOR_LESS_THAN      = '<';

    /**
     * 初始位置
     */
    private     $_positionFirst;

    /**
     * 初始节点
     */
    private     $_nodeFirst;

    /**
     * 节点内位置
     */
    private     $_positionNode;

    /**
     * 当前节点
     */
    private     $_nodeCurrent;

    /**
     * 操作符
     */
    private     $_operator;

    /**
     * 存储
     */
    protected   $_store;

    /**
     * 构造函数
     *
     * @param   BTree_Store $store      存储实例
     * @param   BTree_Node  $node       节点
     * @param   string      $key        关键词
     * @param   string      $operator   运算符
     */
    public  function    __construct (BTree_Store $store, BTree_Node $node, $key, $operator = self::OPERATOR_GREATER_THAN) {

        $this->_store           = $store;
        $this->_operator        = $operator;
        $position               = $this->_position($node, $key);
        list($nodeTarget, $positionTarget)  = $this->_node($node, $position);
        $this->_nodeCurrent     = $this->_nodeFirst     = $nodeTarget;
        $this->_positionNode    = $this->_positionFirst = $positionTarget;
    }

    /**
     * 获取位置
     *
     * @param   BTree_Node  $node   节点
     * @param   string      $key    关健词
     * @return  array               位置列表
     */
    private function _position ($node, $key) {

        $listPosition   = $node->matchPosition($key);

        if (count($listPosition) > 1) {

            return  self::OPERATOR_GREATER_THAN == $this->_operator
                    ? end($listPosition)
                    : current($listPosition);
        }

        return          current($listPosition);
    }

    /**
     * 根据位置获取节点
     *
     * @param   BTree_Node  $node       节点
     * @param   int         $position   位置
     * @return  array                   实际节点和位置
     */
    private function _node ($node, $position) {

        if ($position < 0) {

            $parent                 = $node->parent();
            $pointerLeftNeighbor    = $parent->pointerLeftChild($node->pointer());
            $nodeTarget             = $this->_searchLeftBorderLeaf($pointerLeftNeighbor, $parent);
            $positionTarget         = count($nodeTarget->data()) - 1;
        }

        if ($position > (count($node->data()) - 1)) {

            $parent                 = $node->parent();
            $pointerRightNeighbor   = $parent->pointerRightChild($node->pointer());
            $nodeTarget             = $$this->_searchRightBorderLeaf($pointerRightNeighbor, $parent);
            $positionTarget         = 0;
        }

        if (!isset($nodeTarget)) {

            $nodeTarget     = $node;
            $positionTarget = $position;
        }

        return  array($nodeTarget, $positionTarget);
    }

    /**
     * 当前值
     *
     * @return  int 值
     */
    public  function current () {

        $listValue  = array_values($this->_nodeCurrent->data());

        return      $listValue[$this->_positionNode];
    }

    /**
     * 当前关健词
     *
     * @return  string  关健词
     */
    public  function key () {

        $listKey    = array_keys($this->_nodeCurrent->data());

        return      $listKey[$this->_positionNode];
    }

    /**
     * 下次迭代
     */
    public  function next () {

        return  self::OPERATOR_GREATER_THAN === $this->_operator
                ? $this->_nextRight()
                : $this->_nextLeft();
    }

    /**
     * 恢复初始状态
     */
    public  function rewind () {

        $this->_nodeCurrent     = $this->_nodeFirst;
        $this->_positionNode    = $this->_positionFirst;
    }

    /**
     * 校验游标是否在有效位置
     *
     * @return  bool    校验结果
     */
    public  function valid () {

        return  self::OPERATOR_GREATER_THAN === $this->_operator
                ? !$this->_isRightBorder()
                : !$this->_isLeftBorder();
    }

    /**
     * 是否到左边界
     *
     * @return  bool    校验结果
     */
    private function _isLeftBorder () {

        if ($this->_positionNode < 0 && $this->_nodeCurrent->isLeaf()) {

            list($node, $pointer)   = $this->_leftParent($this->_nodeCurrent);

            return  $node->isNewRoot();
        }

        return  false;
    }

    /**
     * 是否到右边界
     *
     * @return  bool    校验结果
     */
    private function _isRightBorder () {

        if ($this->_positionNode >= count($this->_nodeCurrent->data()) && $this->_nodeCurrent->isLeaf()) {

            list($node, $pointer)  = $this->_rightParent($this->_nodeCurrent);

            return  $node->isNewRoot();
        }

        return  false;
    }

    /**
     * 向右移动游标
     *
     * @return  void    空
     */
    private function _nextRight () {

        if (!$this->_nodeCurrent->isLeaf()) {

            $childPointerRight      = $this->_nodeCurrent->childPointer($this->_positionNode + 1);
            $this->_nodeCurrent     = $this->_searchLeftBorderLeaf($childPointerRight, $this->_nodeCurrent);
            $this->_positionNode    = 0;

            return  ;
        }

        if ($this->_positionNode + 1 > count($this->_nodeCurrent->data()) - 1) {

            list($node, $pointer)   = $this->_rightParent($this->_nodeCurrent);
            $this->_nodeCurrent     = $node;
            $this->_positionNode    = $node->childPosition($pointer);

            return  ;
        }

        ++ $this->_positionNode;
    }

    /**
     * 向左移动游标
     *
     * @return  void    空
     */
    private function _nextLeft () {

        if (!$this->_nodeCurrent->isLeaf()) {

            $childPointerLeft       = $this->_nodeCurrent->childPointer($this->_positionNode);
            $this->_nodeCurrent     = $this->_searchRightBorderLeaf($childPointerLeft, $this->_nodeCurrent);
            $this->_positionNode    = count($this->_nodeCurrent->data()) - 1;

            return  ;
        }

        if ($this->_positionNode - 1 < 0) {

            list($node, $pointer)   = $this->_leftParent($this->_nodeCurrent);
            $this->_nodeCurrent     = $node;
            $this->_positionNode    = $node->childPosition($pointer) - 1;

            return  ;
        }

        -- $this->_positionNode;
    }

    /**
     * 获取存储实例
     *
     * @return  BTree_Store 存储实例
     */
    protected   function _store () {

        return  $this->_store;
    }

    /**
     * 获取左侧上级节点及指针
     *
     * @param   BTree_Node  $node   节点
     * @return  array               节点及指针
     */
    private function _leftParent ($node) {

        return  $node->parent()->leftBorderChild() === $node->pointer() && !$node->parent()->isNewRoot()
                ? $this->_leftParent($node->parent())
                : array($node->parent(), $node->pointer());
    }

    /**
     * 获取右侧上级节点及指针
     *
     * @param   BTree_Node  $node   节点
     * @return  array               节点及指针
     */
    private function _rightParent ($node) {

        return  $node->parent()->rightBorderChild() === $node->pointer() && !$node->parent()->isNewRoot()
                ? $this->_rightParent($node->parent())
                : array($node->parent(), $node->pointer());
    }
}