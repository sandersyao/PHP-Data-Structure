<?php
/**
 * B-Tree插入
 */
final   class   BTree_Insert
    implements BTree_Command {

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

        $key            = $params['key'];
        $value          = $params['value'];
        $currentNode    = $this->_searchNode($key);

        if (BTree_Validate::value($currentNode->match($key))) {

            throw   new Exception('key ' . $key . ' exists');
        }

        $this->_insertNode($currentNode, $key, $value);
    }

    /**
     * 插入节点
     *
     * @param   BTree_Node  $currentNode    当前节点
     * @param   string      $key            关键词
     * @param   int         $value          值
     * @param   int         $pointerLeft    左侧指针
     * @param   int         $pointerRight   右侧指针
     */
    private function _insertNode ($currentNode, $key, $value, $pointerLeft = 0, $pointerRight = 0) {

        if ($this->_isFullNode($currentNode)) {

            return  $this->_isSeparateFirst()
                    ? $this->_insertNodeBeforeSeparate($currentNode, $key, $value, $pointerLeft, $pointerRight)
                    : $this->_insertNodeAfterSeparate($currentNode, $key, $value, $pointerLeft, $pointerRight);
        }

        $currentNode->insert($key, $value, $pointerLeft, $pointerRight);

        return  $this->_store()->writeNode($currentNode, $this->_options);
    }

    /**
     * 先分裂再插入
     *
     * @param   BTree_Node  $currentNode    当前节点
     * @param   string      $key            关键词
     * @param   int         $value          值
     * @param   int         $pointerLeft    左侧指针
     * @param   int         $pointerRight   右侧指针
     */
    private function _insertNodeBeforeSeparate ($currentNode, $key, $value, $pointerLeft, $pointerRight) {

        list($keyMid, $valueMid, $rightNode)    = $currentNode->separateRight();

        if (strval($key) < strval($keyMid)) {

            $currentNode->insert($key, $value, $pointerLeft, $pointerRight);
        } else{

            $rightNode->insert($key, $value, $pointerLeft, $pointerRight);
        }

        $pointerLeftMid     = $this->_store()->writeNode($currentNode, $this->_options);
        $pointerRightMid    = $this->_store()->writeNode($rightNode, $this->_options);

        return              $this->_insertNode($currentNode->parent(), $keyMid, $valueMid, $pointerLeftMid, $pointerRightMid);
    }

    /**
     * 先插入再分裂
     *
     * @param   BTree_Node  $currentNode    当前节点
     * @param   string      $key            关键词
     * @param   int         $value          值
     * @param   int         $pointerLeft    左侧指针
     * @param   int         $pointerRight   右侧指针
     */
    private function _insertNodeAfterSeparate ($currentNode, $key, $value, $pointerLeft, $pointerRight) {

        $currentNode->insert($key, $value, $pointerLeft, $pointerRight);
        list($keyMid, $valueMid, $rightNode)    = $currentNode->separateRight();
        $pointerLeftMid     = $this->_store()->writeNode($currentNode);
        $pointerRightMid    = $this->_store()->writeNode($rightNode);

        return              $this->_insertNode($currentNode->parent(), $keyMid, $valueMid, $pointerLeftMid, $pointerRightMid);
    }

    /**
     * 是否是先分裂
     *
     * @return  bool    判断结果
     */
    private function _isSeparateFirst () {

        return  $this->_options()->numberSlots() % 2 == 1;
    }

    /**
     * 是否是满节点
     *
     * @return  bool    判断结果
     */
    private function _isFullNode (BTree_Node $node) {

        return  count($node->data()) >= $this->_options()->numberSlots();
    }
}