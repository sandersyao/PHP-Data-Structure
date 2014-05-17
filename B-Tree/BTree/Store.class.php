<?php
/**
 * B-Tree文件存储
 */
class   BTree_Store {

    const   NUMBER_OPTIONS_PARAMS   = 2;

    /**
     * 文件地址=>实例图
     */
    private static  $_fileMap   = array();

    /**
     * 配置
     */
    private $_options;

    /**
     * 配置数据长度
     */
    private $_optionsLength;

    /**
     * 指针区域长度
     */
    private $_pointerAreaSize;

    /**
     * 关键词区域长度
     */
    private $_keyAreaSize;

    /**
     * 值区域长度
     */
    private $_valueAreaSize;

    /**
     * 根节点指针区域长度
     */
    private $_pointerRootSize;

    /**
     * 文件句柄
     */
    private $_fileHandle;

    /**
     * 根节点指针
     */
    private $_pointerRoot;

    /**
     * 创建实例
     *
     * @param   string      $file       文件地址
     * @param   array       $options    配置
     * @return  BTree_Store             本类实例
     */
    public  static  function open ($file, BTree_Options $options) {

        if (!is_file($file)) {

            touch($file);
        }

        $realpath   = realpath($file);

        if (!isset(self::$_fileMap[$realpath]) || !(self::$_fileMap[$realpath] instanceof self)) {

            $fileHandle                 = fopen($realpath, 'r+');
            self::$_fileMap[$realpath]  = new self($fileHandle, $options);
        }

        return      self::$_fileMap[$realpath];
    }

    /**
     * 构造函数
     *
     * @param   resource        $fileHandle 文件句柄
     * @param   BTree_Options   $options    配置
     */
    private function __construct ($fileHandle, BTree_Options $options) {

        $this->_fileHandle  = $fileHandle;
        $this->_initialize($options);
    }

    /**
     * 析构函数
     */
    public  function __destruct () {

        fclose($this->_fileHandle);
        $realPath   = array_search($this, self::$_fileMap);
        unset(self::$_fileMap[$realPath]);
    }

    /**
     * 读取节点
     *
     * @param   int         $pointer    指针
     * @param   BTree_Node  $parentNode 上级节点
     * @return  BTree_Node              节点
     */
    public  function readNode ($pointer, $parentNode = NULL) {

        fseek($this->_fileHandle, $pointer);
        $content        = fread($this->_fileHandle, $this->_nodeSize());
        $pointerString  = substr($content, 0, $this->_pointerAreaSize());
        $pointerList    = unpack('l*', $pointerString);
        $keyString      = substr($content, $this->_pointerAreaSize(), $this->_keyAreaSize());
        $keyClips       = str_split($keyString, $this->_options->keyLength());
        $keyList        = array_filter(array_map('rtrim', $keyClips), array('BTree_Validate', 'key'));
        $valueString    = substr($content, $this->_pointerAreaSize() + $this->_keyAreaSize(), $this->_valueAreaSize());
        $valueList      = array_filter(unpack('l*', $valueString), array('BTree_Validate', 'value'));
        $data           = array_combine($keyList, $valueList);
        $node           = new BTree_Node($data, $pointerList, $parentNode, $pointer);

        return          $node;
    }

    /**
     * 写入节点
     *
     * @param   BTree_Node      $node       节点
     * @return  int                         指针
     */
    public  function writeNode (BTree_Node $node) {

        $pointer            = $this->_seekForWrite($node);
        $childrenPointer    = array_slice($node->children(), 0, $this->_options->numberChildren());
        $childrenContent    = str_pad(implode('', array_map(array($this, '_longPack'), $childrenPointer)), $this->_pointerAreaSize(), $this->_longPack(0));
        $keyContent         = str_pad(implode('', array_map(array($this, '_keyPad'), array_keys($node->data()))), $this->_keyAreaSize(), ' ');
        $valueContent       = str_pad(implode('', array_map(array($this, '_longPack'), $node->data())), $this->_valueAreaSize(), $this->_longPack(0));
        $content            = $childrenContent . $keyContent . $valueContent;
        fwrite($this->_fileHandle, $content);

        return  $pointer;
    }

    /**
     * 写入前移动游标位置
     */
    private function _seekForWrite (BTree_Node $node) {

        if ($node->isNew()) {

            fseek($this->_fileHandle, 0, SEEK_END);

            return  ftell($this->_fileHandle);
        }

        if ($node->isNewRoot()) {

            fseek($this->_fileHandle, 0, SEEK_END);
            $pointer    = ftell($this->_fileHandle);

            if (!BTree_Validate::pointer($pointer)) {

                $this->saveOptions($this->_options);
                $pointer    = $this->_pointerRootDefault();
            }

            $this->rootPointer($pointer);
            $this->saveRootPointer();
            fseek($this->_fileHandle, 0, SEEK_END);

            return      $pointer;

        }

        fseek($this->_fileHandle, $node->pointer());

        return      $node->pointer();
    }

    /**
     * 保存配置
     *
     * @param   BTree_Options   $options    配置模型
     */
    public  function saveOptions ($options) {

        $string = $this->_encodeOptions($options);
        fseek($this->_fileHandle, 0);
        fwrite($this->_fileHandle, $string);
    }

    /**
     * 读取配置
     *
     * @return  BTree_Options   文件中的配置模型
     */
    public  function readOptions () {

        fseek($this->_fileHandle, 0);
        $optionsStr = fread($this->_fileHandle, $this->_optionsLength());

        if ('' == $optionsStr) {

            return  false;
        }

        $options    = $this->_decodeOptions($optionsStr);
        $this->_initialize($options);
        return      $options;
    }

    /**
     * 长整型组包
     *
     * @param   int     $int    整型值
     * @return  string          包
     */
    private function _longPack ($int) {

        return  pack('l', $int);
    }

    /**
     * 关键词补满
     *
     * @param   string          $key        关键词
     * @return  string                      补充结果
     */
    private function _keyPad ($key) {

        return  str_pad($key, $this->_options->keyLength(), ' ');
    }

    /**
     * 编码配置数据
     *
     * @param   BTree_Options   $options    配置数据
     * @return  string                      编码后的配置数据
     */
    private function _encodeOptions (BTree_Options $options) {

        return  pack(
            'l*',
            $options->numberSlots(),
            $options->keyLength()
        );
    }

    /**
     * 解码配置数据
     *
     * @param   string          $content    文件中的配置数据
     * @param   BTree_Options   $options    配置实例
     * @return  BTree_Options               解码后的配置数据
     */
    private function _decodeOptions ($content, BTree_Options $options) {

        @list($null, $slots, $keyLength)   = unpack('l*', $content);

        $options->numberSlots();
        $options->keyLength();

        return  $options;
    }

    /**
     * 根节点指针
     *
     * @param   int $pointer    指针
     * @return  int             指针
     */
    public  function rootPointer ($pointer = NULL) {

        $pointerOld = NULL == $this->_pointerRoot
                    ? $this->_pointerRootDefault()
                    : $this->_pointerRoot;

        if (is_numeric($pointer)) {

            $this->_pointerRoot = (int) $pointer;
        }

        return      $pointerOld;
    }

    /**
     * 保存根节点指针
     */
    public  function saveRootPointer () {

        fseek($this->_fileHandle, $this->_optionsLength());
        fwrite($this->_fileHandle, pack('l', $this->rootPointer()));
    }

    /**
     * 读取指针
     */
    private function _readRootPointer () {

        fseek($this->_fileHandle, $this->_optionsLength());
        $pointerStr         = fread($this->_fileHandle, $this->_pointerRootSize());

        if ('' != $pointerStr) {

            @list($null, $pointer)  = unpack('l', $pointerStr);
        } else {
            $pointer    = NULL;
        }

        return              $pointer;
    }

    /**
     * 初始化配置
     *
     * @param   BTree_Options   $options    配置
     */
    private function _initialize (BTree_Options $options) {

        $this->_options         = $options;
        $longSize               = strlen(pack('l', 1));
        $this->_optionsLength   = $longSize * self::NUMBER_OPTIONS_PARAMS;
        $this->_pointerAreaSize = $options->numberChildren() * $longSize;
        $this->_keyAreaSize     = $options->numberSlots() * $options->keyLength();
        $this->_valueAreaSize   = $options->numberSlots() * $longSize;
        $this->_pointerRootSize = $longSize;
        $this->_pointerRoot     = $this->_readRootPointer();
    }

    /**
     * 配置保存区域长度
     *
     * @return  int 配置保存区域长度
     */
    private function _optionsLength () {

        return  $this->_optionsLength;
    }

    /**
     * 节点尺寸
     *
     * @return  int 尺寸
     */
    private function _nodeSize () {

        return  $this->_pointerAreaSize() +
                $this->_keyAreaSize() +
                $this->_valueAreaSize();
    }

    /**
     * 指针区域长度
     *
     * @return  int 尺寸
     */
    private function _pointerAreaSize () {

        return  $this->_pointerAreaSize;
    }

    /**
     * 关键词区域长度
     *
     * @return  int 尺寸
     */
    private function _keyAreaSize () {

        return  $this->_keyAreaSize;
    }

    /**
     * 值区域长度
     *
     * @return  int                         尺寸
     */
    private function _valueAreaSize () {

        return  $this->_valueAreaSize;
    }

    /**
     * 默认根节点指针
     *
     * @return  int 默认根节点指针
     */
    private function _pointerRootDefault () {

        return  $this->_optionsLength() + $this->_pointerRootSize();
    }

    /**
     * 根节点指针占用长度
     *
     * @return  int 根节点指针长度
     */
    private function _pointerRootSize () {

        return  $this->_pointerRootSize;
    }
}