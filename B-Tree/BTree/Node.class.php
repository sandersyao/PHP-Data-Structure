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

    /**
     * 构造函数
     *
     * @param   arary           $data       数据
     * @param   array           $children   子节点指针
     * @param   BTree_Node|null $parent     上级节点
     * @param   int             $pointer    当前节点指针
     */
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

    /**
     * 返回指针
     *
     * @return  int 当前节点指针
     */
    public  function pointer () {

        return  $this->_pointer;
    }

    /**
     * 匹配关键词对应的值
     *
     * @param   string      $key    关键词
     * @return  int|bool            值|如果关键词不存在返回false
     */
    public  function match ($key) {

        if (isset($this->_data[$key])) {

            return  $this->_data[$key];
        }

        return  false;
    }

    /**
     * 匹配关键词对应的子节点指针
     *
     * @param   string      $key    关键词
     * @return  int|bool            子节点指针|如果关键词存在返回false
     */
    public  function matchChildren ($key) {

        $keyList    = array_keys($this->_data);

        if (in_array($key, $keyList)) {

            return  false;
        }

        $position   = $this->_dichotomySearch($key, $keyList);

        return      isset($this->_children[$position])  ? $this->_children[$position]   : 0;
    }

    /**
     * 匹配位置
     *
     * @param   string  $key    键
     * @return  array           位置列表
     */
    public  function matchPosition ($key) {

        $keyList    = array_keys($this->_data);
        $offset     = array_search($key, $keyList);

        if (is_int($offset)) {

            return  array($offset);
        }

        $offset     = $this->_dichotomySearch($key, $keyList);

        return      array($offset - 1, $offset);
    }

    /**
     * 插入key
     *
     * @param   string  $key            关键词
     * @param   int     $value          值
     * @param   int     $pointerLeft    左侧子节点指针
     * @param   int     $pointerRight   右侧子节点指针
     * @return  bool                    执行结果
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

        return  true;
    }

    /**
     * 删除key
     *
     * @param   string  $key        关键词
     * @param   string  $deleteFlag 从关键词左侧或者右侧删除子节点指针
     * @return  bool                执行结果
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

        return          true;
    }

    /**
     * 左边缘子节点指针
     *
     * @return  int 子节点指针
     */
    public  function leftBorderChild () {

        reset($this->_children);

        return  count($this->_children) > 0 ? current($this->_children) : 0;
    }

    /**
     * 右边缘子节点指针
     *
     * @return  int 子节点指针
     */
    public  function rightBorderChild () {

        return  count($this->_children) > 0 ? end($this->_children) : 0;
    }

    /**
     * 指针左侧相邻子节点指针
     *
     * @param   int $pointer    指针
     * @return  int             子节点指针
     */
    public  function pointerLeftChild ($pointer) {

        $offset = array_search($pointer, $this->_children);

        return  isset($this->_children[$offset - 1])
                ? $this->_children[$offset - 1]
                : self::POINTER_NEW;
    }

    /**
     * 指针右侧相邻子节点指针
     *
     * @param   int $pointer    指针
     * @return  int             子节点指针
     */
    public  function pointerRightChild ($pointer) {

        $offset = array_search($pointer, $this->_children);

        return  isset($this->_children[$offset + 1])
                ? $this->_children[$offset + 1]
                : self::POINTER_NEW;
    }

    /**
     * 指针左侧相邻关键词
     *
     * @param   int     $pointer    指针
     * @return  string              关键词
     */
    public  function pointerLeftKey ($pointer) {

        $offset     = array_search($pointer, $this->_children);
        $keyList    = array_keys($this->_data);

        return      isset($keyList[$offset - 1])
                    ? $keyList[$offset - 1]
                    : NULL;
    }

    /**
     * 指针右侧相邻关键词
     *
     * @param   int     $pointer    指针
     * @return  string              关键词
     */
    public  function pointerRightKey ($pointer) {

        $offset     = array_search($pointer, $this->_children);
        $keyList    = array_keys($this->_data);

        return      isset($keyList[$offset])
                    ? $keyList[$offset]
                    : NULL;
    }

    /**
     * 关键词左侧子节点指针
     *
     * @param   string      $key    关键词
     * @return  int|bool            子节点指针|失败返回false
     */
    public  function keyLeftChild ($key) {

        $keyList    = array_keys($this->_data);
        $offset     = array_search($key, $keyList);

        if (false === $offset) {

            return  false;
        }

        return      $this->_children[$offset];
    }

    /**
     * 关键词右侧子节点指针
     *
     * @param   string      $key    关键词
     * @return  int|bool            子节点指针|失败返回false
     */
    public  function keyRightChild ($key) {

        $keyList    = array_keys($this->_data);
        $offset     = array_search($key, $keyList);

        if (false === $offset) {

            return  false;
        }

        return      $this->_children[$offset + 1];
    }

    /**
     * 替换关键词
     *
     * @param   string      $keyOld     目标关键词
     * @param   string      $keyNew     要替换的关键词
     * @param   int         $valueNew   要替换的值
     * @return  bool                    执行结果
     */
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

        return          true;
    }

    /**
     * 返回本节点是否是叶节点
     *
     * @return  bool    判断结果
     */
    public  function isLeaf () {

        reset($this->_children);

        return  0 == count($this->_children) || current($this->_children) <= 0;
    }

    /**
     * 二分法查找位置 (该关键词不存在)
     *
     * @param   string  $key    关键词
     * @param   array   $list   关键词列表
     * @param   int     $offset 初始位置
     * @return  int             目地位置
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

    /**
     * 获取左端关键词
     *
     * @return  string  关键词
     */
    public  function leftBorderKey () {

        $keyList    = array_keys($this->_data);

        return  current($keyList);
    }

    /**
     * 获取右端关键词
     *
     * @return  string  关键词
     */
    public  function rightBorderKey () {

        $keyList    = array_keys($this->_data);

        return  end($keyList);
    }

    /**
     * 向右侧分裂节点
     *
     * @return  array|bool  成功返回数组包含 中间关键词 中间值 右侧节点|失败返回false
     */
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

    /**
     * 合并节点
     *
     * @param   BTree_Node  $node   待合并节点
     */
    public  function merge (BTree_Node $node) {

        $offset         = 0;
        $pointerList    = $node->children();

        foreach ($node->data() as $key => $value) {

            $this->insert($key, $value, $pointerList[$offset], $pointerList[$offset + 1]);
            ++ $offset;
        }
    }

    /**
     * 返回上级节点
     *
     * @return  BTree_Node  上级节点
     */
    public  function parent () {

        return  $this->_parent instanceof self
                ? $this->_parent
                : new self(array(), array(), NULL, self::POINTER_NEW_ROOT);
    }

    /**
     * 返回子节点指针列表
     *
     * @return  array   子节点指针列表
     */
    public  function children () {

        return  $this->_children;
    }

    /**
     * 返回当前节点数据
     *
     * @return  array   当前节点数据
     */
    public  function data () {

        return  $this->_data;
    }

    /**
     * 判断是否为新节点
     *
     * @return  bool    判断结果
     */
    public  function isNew () {

        return  self::POINTER_NEW == $this->pointer();
    }

    /**
     * 判断是否为新的根节点
     *
     * @return  bool    判断结果
     */
    public  function isNewRoot () {

        return  self::POINTER_NEW_ROOT == $this->pointer();
    }

    /**
     * 返回子节点指针位置
     *
     * @param   int         $pointer    指针
     * @return  int|bool                位置
     */
    public  function childPosition ($pointer) {

        return  array_search($pointer, $this->_children);
    }

    /**
     * 返回位置上的子节点指针
     *
     * @param   int         $offset 位置
     * @return  int|bool            指针
     */
    public  function childPointer ($offset) {

        return  isset($this->_children[$offset])
                ? $this->_children[$offset]
                : false;
    }
}