<?php
/**
 * 节点
 */
class   BTree_Node {

    /**
     * 新节点指针
     */
    const   POINTER_NEW         = 0;

    /**
     * 新根节点指针
     */
    const   POINTER_NEW_ROOT    = -1;

    /**
     * 删除关键词右侧指针(子节点)标识
     */
    const   DELETE_FLAG_RIGHT   = '>';

    /**
     * 删除关键词左侧指针(子节点)标识
     */
    const   DELETE_FLAG_LEFT    = '<';

    /**
     * 数据 图结构 key => value
     */
    private $_data;

    /**
     * 子节点指针
     */
    private $_children;

    /**
     * 上级节点实例
     */
    private $_parent;

    /**
     * 节点指针
     */
    private $_pointer;

    public  function __construct ($data = array(), $children = array(), $parent = NULL, $pointer = self::POINTER_NEW) {

        $keyList            = array_map('strval', array_keys($data));
        $valueList          = array_map('intval', array_values($data));
        $this->_data        = array_combine($keyList, $valueList);
        $this->_children    = count($data) < count($children)
                            ? array_slice(array_values($children), 0, count($data) + 1)
                            : array_pad(array_values($children), count($data) + 1, 0);
        $this->_parent      = $parent;
        $this->_pointer     = $pointer;
    }

    public  function pointer () {

        return  $this->_pointer;
    }

    public  function match ($key) {

        if (isset($this->_data[$key])) {

            return  $this->_data[$key];
        }

        return  false;
    }

    public  function matchChildren ($key) {

        $keyList    = array_keys($this->_data);

        if (in_array($key, $keyList)) {

            return  false;
        }

        $position   = $this->_dichotomySearch($key, $keyList);

        return      isset($this->_children[$position])  ? $this->_children[$position]   : 0;
    }

    /**
     * 插入key
     */
    public  function insert ($key, $value, $pointerLeft = 0, $pointerRight = 0) {

        $keyList            = array_keys($this->_data);
        $key                = strval($key);
        $value              = intval($value);

        if (in_array($key, $keyList)) {

            return  false;
        }

        $offset             = $this->_dichotomySearch($key, $keyList);
        $valueList          = array_values($this->_data);
        array_splice($keyList, $offset, 0, $key);
        array_splice($valueList, $offset, 0, $value);
        array_splice($this->_children, $offset, 1, array($pointerLeft, $pointerRight));
        $this->_children    = array_slice($this->_children, 0, count($keyList) + 1);
        $this->_data        = array_combine($keyList, $valueList);
    }

    /**
     * 删除key
     */
    public  function delete ($key, $deleteFlag = self::DELETE_FLAG_LEFT) {

        $keyList        = array_keys($this->_data);
        $offset         = array_search($key, $keyList);

        if (false === $offset) {

            return  false;
        }

        if ($this->isLeaf()) {

            array_splice($this->_children, $offset + 1, 1);
        }

        $valueList      = array_values($this->_data);
        array_splice($keyList, $offset, 1);
        array_splice($valueList, $offset, 1);

        if (self::DELETE_FLAG_LEFT === $deleteFlag) {

            array_splice($this->_children, $offset, 1);
        } else {

            array_splice($this->_children, $offset + 1, 1);
        }

        $this->_data    = array_combine($keyList, $valueList);
    }

    public  function leftBorderChild () {

        reset($this->_children);

        return  count($this->_children) > 0 ? current($this->_children) : 0;
    }

    public  function rightBorderChild () {

        return  count($this->_children) > 0 ? end($this->_children) : 0;
    }

    public  function pointerLeftChild ($pointer) {

        $offset = array_search($pointer, $this->_children);

        return  isset($this->_children[$offset - 1])
                ? $this->_children[$offset - 1]
                : self::POINTER_NEW;
    }

    public  function pointerRightChild ($pointer) {

        $offset = array_search($pointer, $this->_children);

        return  isset($this->_children[$offset + 1])
                ? $this->_children[$offset + 1]
                : self::POINTER_NEW;
    }

    public  function pointerLeftKey ($pointer) {

        $offset     = array_search($pointer, $this->_children);
        $keyList    = array_keys($this->_data);

        return      isset($keyList[$offset - 1])
                    ? $keyList[$offset - 1]
                    : NULL;
    }

    public  function pointerRightKey ($pointer) {

        $offset     = array_search($pointer, $this->_children);
        $keyList    = array_keys($this->_data);

        return      isset($keyList[$offset])
                    ? $keyList[$offset]
                    : NULL;
    }

    public  function keyLeftChild ($key) {

        $keyList    = array_keys($this->_data);
        $offset     = array_search($key, $keyList);

        if (false === $offset) {

            return  false;
        }

        return      $this->_children[$offset];
    }

    public  function keyRightChild ($key) {

        $keyList    = array_keys($this->_data);
        $offset     = array_search($key, $keyList);

        if (false === $offset) {

            return  false;
        }

        return      $this->_children[$offset + 1];
    }

    public  function replaceKey ($keyOld, $keyNew, $valueNew) {

        $keyList        = array_keys($this->_data);
        $offset         = array_search($keyOld, $keyList);

        if (false === $offset) {

            return  false;
        }

        $valueList      = array_values($this->_data);
        array_splice($keyList, $offset, 1, $keyNew);
        array_splice($valueList, $offset, 1, $valueNew);
        $this->_data    = array_combine($keyList, $valueList);
    }

    public  function isLeaf () {

        reset($this->_children);

        return  0 == count($this->_children) || current($this->_children) <= 0;
    }

    /**
     * 二分法查找位置 (该值不存在)
     */
    private function _dichotomySearch ($key, $list, $offset = 0) {

        $count      = count($list);

        if (0 == $count) {

            return  0;
        }

        if (1 == $count) {

            reset($list);

            return  strval($key) > strval(current($list)) ? $offset + 1   : $offset;
        }

        $offsetMiddle   = floor($count / 2);
        $listLeft       = array_splice($list, 0, $offsetMiddle);
        $listRight      = $list;

        return      end($listLeft) > $key
                    ? $this->_dichotomySearch($key, $listLeft, $offset)
                    : $this->_dichotomySearch($key, $listRight, $offset + $offsetMiddle);
    }

    public  function getLeftBorderKey () {

        $keyList    = array_keys($this->_data);

        return  current($keyList);
    }

    public  function getRightBorderKey () {

        $keyList    = array_keys($this->_data);

        return  end($keyList);
    }

    public  function separateRight () {

        $count              = count($this->_data);

        if (0 === $count % 2) {

            return  false;
        }

        $midOffsetData      = floor($count / 2);
        $keyList            = array_keys($this->_data);
        $valueList          = array_values($this->_data);
        $midKey             = $keyList[$midOffsetData];
        $midValue           = $valueList[$midOffsetData];
        $keyListLeft        = array_slice($keyList, 0, $midOffsetData);
        $keyListRight       = array_slice($keyList, $midOffsetData + 1);
        $valueListLeft      = array_slice($valueList, 0, $midOffsetData);
        $valueListRight     = array_slice($valueList, $midOffsetData + 1);
        $childrenLeft       = array_slice($this->_children, 0, ($count + 1) / 2);
        $childrenRight      = array_slice($this->_children, ($count + 1) / 2);
        $this->_data        = array_combine($keyListLeft, $valueListLeft);
        $this->_children    = $childrenLeft;
        $dataRight          = array_combine($keyListRight, $valueListRight);
        $rightNode          = new self($dataRight, $childrenRight);

        return              array($midKey, $midValue, $rightNode);
    }

    public  function merge (BTree_Node $node) {

        $offset         = 0;
        $pointerList    = $node->children();

        foreach ($node->data() as $key => $value) {

            $this->insert($key, $value, $pointerList[$offset], $pointerList[$offset + 1]);
            ++ $offset;
        }
    }

    public  function parent () {

        return  $this->_parent instanceof self
                ? $this->_parent
                : new self(array(), array(), NULL, self::POINTER_NEW_ROOT);
    }

    public  function children () {

        return  $this->_children;
    }

    public  function data () {

        return  $this->_data;
    }

    public  function isNew () {

        return  self::POINTER_NEW == $this->pointer();
    }

    public  function isNewRoot () {

        return  self::POINTER_NEW_ROOT == $this->pointer();
    }
}