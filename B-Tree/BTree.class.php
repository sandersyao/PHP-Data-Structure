<?php
class   BTree {

    /**
     * 键数量
     */
    const   NUM_SLOTS       = 3;

    /**
     * 键长度
     */
    const   KEY_LENGTH_MAX  = 10;

    private $_fileHandle;

    private $_options;

    public  static  function open ($file, $options = array()) {

        $mod        = is_file($file)    ? 'r+'  : 'w+';
        $fileHandle = fopen($file, $mod);

        return      new self($fileHandle, $options);
    }

    private function __construct ($fileHandle, $options = array()) {

        $this->_fileHandle  = $fileHandle;

        if (isset($options['root_pointer'])) {

            unset($options['root_pointer']);
        }

        $this->_setOptions($options);
    }

    public  function __destruct () {

        fclose($this->_fileHandle);
    }

    public  function select ($key) {

        $node   = $this->_searchNode($key);

        if ($node instanceof BTreeNode) {

            return  $node->match($key);
        }

        return  false;
    }

    public  function insert ($key, $value) {

        if (!$this->_validateValue($value)) {

            throw   new Exception('invalid value');
        }

        $currentNode    = $this->_searchNode($key);

        if ($this->_validateValue($currentNode->match($key))) {

            throw   new Exception('key exists');
        }

        if ($this->_isEmptyNode($currentNode)) {

            $this->_saveOptions();
        }

        $this->_insertNode($currentNode, $key, $value);
    }

    public  function update ($key, $value) {}

    public  function delete ($key) {

        $currentNode    = $this->_searchNode($key);

        if (!$this->_validateValue($currentNode->match($key))) {

            throw   new Exception('key not exists');
        }

        if ($this->_isEmptyNode($currentNode)) {

            throw   new Exception('key not exists');
        }

        if (!$currentNode->isLeaf()) {

            list($currentNode, $key)    = $this->_moveKeyToLeaf($currentNode, $key);
        }

        $this->_deleteKeyFromLeaf($currentNode, $key);
    }

    private function _moveKeyToLeaf (BTreeNode $node, $key) {

        $value      = $node->match($key);
        $leafRight  = $this->_searchLeftBorderLeaf($node->keyRightChild($key), $node);
        $keyRight   = $leafRight->getLeftBorderKey();
        $valueRight = $leafRight->match($keyRight);
        $node->replaceKey($key, $keyRight, $valueRight);
        $this->_writeNode($node);

        return      array($leafRight, $keyRight);
    }

    private function _deleteKeyFromLeaf (BTreeNode $node, $key) {

        $parentNode     = $node->parent();

        if (
            count($node->data()) > $this->_leastNumberKeys() ||
            !($parentNode instanceof BTreeNode) ||
            $this->_isNewRootNode($parentNode)
        ) {

            $node->delete($key);
            $this->_writeNode($node);

            return  true;
        }

        if ($this->_deleteMoveRight($node, $key) || $this->_deleteMoveLeft($node, $key)) {

            return  true;
        }

        return  false;
    }

    private function _deleteMoveLeft ($node, $key) {

        $parentNode         = $node->parent();
        $neghborLeft        = $this->_neighborLeft($node);

        if (!($neghborLeft instanceof BTreeNode)) {

            return  false;
        }

        $keyParentLeft      = $parentNode->pointerLeftKey($node->getPointer());

        if (count($neghborLeft->data()) > $this->_leastNumberKeys()) {

            $valueParentLeft    = $parentNode->match($keyParentLeft);
            $keyLeft            = $neghborRight->getRightBorderKey();
            $valueLeft          = $neghborRight->match($keyRight);
            $node->delete($key);
            $node->insert($keyParentLeft, $valueParentLeft);
            $parentNode->replaceKey($keyParentLeft, $keyLeft, $valueLeft);
            $neghborRight->delete($keyLeft);
            $this->_writeNode($node);
            $this->_writeNode($parentNode);
            $this->_writeNode($neghborLeft);

            return              true;
        }

        $this->_deleteMerge($keyParentLeft, $node, $parentNode, $keyParentLeft);

        return          true;
    }

    private function _deleteMoveRight ($node, $key) {

        $parentNode         = $node->parent();
        $neghborRight       = $this->_neighborRight($node);

        if (!($neghborRight instanceof BTreeNode)) {

            return  false;
        }

        $keyParentRight     = $parentNode->pointerRightKey($node->getPointer());

        if (count($neghborRight->data()) > $this->_leastNumberKeys()) {

            $valueParentRight   = $parentNode->match($keyParentRight);
            $keyRight           = $neghborRight->getLeftBorderKey();
            $valueRight         = $neghborRight->match($keyRight);
            $node->delete($key);
            $node->insert($keyParentRight, $valueParentRight);
            $parentNode->replaceKey($keyParentRight, $keyRight, $valueRight);
            $neghborRight->delete($keyRight);
            $this->_writeNode($node);
            $this->_writeNode($parentNode);
            $this->_writeNode($neghborRight);

            return              true;
        }

        $this->_deleteMerge($node, $neghborRight, $parentNode, $keyParentRight, $key);

        return          true;
    }

    private function _deleteMerge ($left, $right, $parent, $midKey, $key) {

        $midValue   = $parent->match($midKey);
        $nextParent = $parent->parent();
        $left->insert($midKey, $midValue);
        $left->merge($right);
        $parent->delete($midKey, false);
        $left->delete($key);

        if (count($parent->data()) > 0) {

            $this->_writeNode($parent);
        } else {

            $this->_options['root_pointer'] = $left->getPointer();
            $this->_saveOptions();
        }

        if (
            count($parent->data()) <= $this->_leastNumberKeys() &&
            $nextParent instanceof BTreeNode &&
            !$this->_isNewRootNode($nextParent)
        ) {

            $neghborRight   = $this->_neighborRight($parent);

            if ($neghborRight instanceof BTreeNode) {

                $nextRight  = $neghborRight;
                $nextLeft   = $parent;
                $nextMidKey = $nextParent->pointerRightKey($parent->getPointer());
            }

            $neghborLeft    = $this->_neighborLeft($parent);

            if ($neghborLeft instanceof BTreeNode) {

                $nextRight  = $parent;
                $nextLeft   = $neghborLeft;
                $nextMidKey = $nextParent->pointerRightKey($neghborLeft->getPointer());
            }

            $this->_deleteMerge($nextLeft, $nextRight, $nextParent, $nextMidKey, $midKey);
        }

        $this->_writeNode($left);

        return      $left;
    }

    private function _isNewRootNode ($node) {

        return  $node->getPointer() == BTreeNode::POINTER_NEW_ROOT;
    }

    private function _neighborRight (BTreeNode $node) {

        $parentNode     = $node->parent();
        $pointerRight   = $parentNode->pointerRightChild($node->getPointer());

        if ($this->_validatePointer($pointerRight)) {

            return  $this->_readNode($pointerRight);
        }

        return          false;
    }

    private function _neighborLeft (BTreeNode $node) {

        $parentNode     = $node->parent();
        $pointerLeft    = $parentNode->pointerLeftChild($node->getPointer());

        if ($this->_validatePointer($pointerLeft)) {

            return  $this->_readNode($pointerLeft);
        }

        return          false;
    }

    /**
     * 合并删除
     */
    private function _mergeDelete ($node, $key) {

        $parentNode         = $node->parent();

        if (count($node->data()) > $this->_leastNumberKeys() || !($parentNode instanceof BTreeNode)) {

            $node->delete($key);
            $this->_writeNode($node);

            return  ;
        }

        if (
            $this->_exchangeNeighborRight($node) ||
            $this->_exchangeNeighborLeft($node)
        ) {

            return  ;
        }

        $pointerLeft        = $parentNode->pointerLeftChild($node->getPointer());

        if ($this->_validatePointer($pointerLeft)) {

            $nodeLeft       = $this->_readNode($pointerLeft, $parentNode);
            $keyParent      = $parentNode->pointerRightKey($pointerLeft);
            $valueParent    = $parentNode->match($keyParent);
            $this->_mergeDelete($parentNode, $keyParent);
            $nodeLeft       = $this->_mergeLeft($keyParent, $valueParent, $nodeLeft, $node);
            $nodeLeft->delete($key);
            $this->_writeNode($nodeLeft);

            return  ;
        }

        $pointerRight       = $parentNode->pointerRightChild($node->getPointer());

        if ($this->_validatePointer($pointerRight)) {

            $nodeRight      = $this->_readNode($pointerRight, $parentNode);
            $keyParent      = $parentNode->pointerRightKey($pointerRight);
            $valueParent    = $parentNode->match($keyParent);
            $this->_mergeDelete($parentNode, $keyParent);
            $nodeRight      = $this->_mergeRight($keyParent, $valueParent, $node, $nodeRight);
            $nodeRight->delete($key);
            $this->_writeNode($nodeRight);

            return  ;
        }
    }

    private function _mergeLeft ($keyParent, $valueParent, $nodeLeft, $nodeRight) {

        $nodeRight->insert($keyParent, $valueParent, $nodeLeft->rightBorderChild(), $nodeRight->leftBorderChild());
        $dataRight      = $nodeRight->data();
        $childrenRight  = $nodeRight->children();
        $offset         = 0;

        foreach ($dataRight as $keyRight => $valueRight) {

            $nodeLeft->insert($keyRight, $valueRight, $childrenRight[$offset], $childrenRight[$offset + 1]);
            ++ $offset;
        }

        return          $nodeLeft;
    }

    private function _mergeRight ($keyParent, $valueParent, $nodeLeft, $nodeRight) {

        $nodeLeft->insert($keyParent, $valueParent, $nodeLeft->rightBorderChild(), $nodeRight->leftBorderChild());
        $dataLeft       = $nodeLeft->data();
        $childrenLeft   = $nodeLeft->children();
        $dataRight      = $nodeRight->data();
        $childrenRight  = $nodeRight->children();
        $offset         = 0;

        foreach ($dataLeft as $keyLeft => $valueLeft) {

            $nodeRight->insert($keyLeft, $valueLeft, $childrenLeft[$offset], $childrenLeft[$offset + 1]);
            ++ $offset;
        }

        return          $nodeRight;
    }

    /**
     * 节点所能拥有的最低关键字保有量
     */
    private function _leastNumberKeys () {

        return  ceil($this->_numberSlots() / 2) - 1;
    }

    private function _searchLeftBorderLeaf ($pointer, $parentNode) {

        $currentNode        = $this->_readNode($pointer, $parentNode);

        if ($currentNode->isLeaf()) {

            return  $currentNode;
        }

        list($leftPointer)  = $currentNode->children();

        return              $this->_searchLeftBorderLeaf($leftPointer, $currentNode);
    }

    private function _isEmptyNode ($node) {

        return  empty($node->data());
    }

    /**
     * 插入节点
     */
    private function _insertNode ($currentNode, $key, $value, $pointerLeft = 0, $pointerRight = 0) {

        if ($this->_isFullNode($currentNode)) {

            if ($this->_isSeparateFirst()) {

                list($keyMid, $valueMid, $rightNode)    = $currentNode->separateRight();

                if (strval($key) < strval($keyMid)) {

                    $currentNode->insert($key, $value, $pointerLeft, $pointerRight);
                } else{

                    $rightNode->insert($key, $value, $pointerLeft, $pointerRight);
                }

                $pointerLeftMid     = $this->_writeNode($currentNode);
                $pointerRightMid    = $this->_writeNode($rightNode);

                return              $this->_insertNode($currentNode->parent(), $keyMid, $valueMid, $pointerLeftMid, $pointerRightMid);
            }

            $currentNode->insert($key, $value, $pointerLeft, $pointerRight);
            list($keyMid, $valueMid, $rightNode)    = $currentNode->separateRight();
            $pointerLeftMid     = $this->_writeNode($currentNode);
            $pointerRightMid    = $this->_writeNode($rightNode);

            return              $this->_insertNode($currentNode->parent(), $keyMid, $valueMid, $pointerLeftMid, $pointerRightMid);
        }

        $currentNode->insert($key, $value, $pointerLeft, $pointerRight);

        return  $this->_writeNode($currentNode);
    }

    /**
     * 匹配到节点
     * 两种情况： 找到返回节点 未找到 返回叶子节点
     */
    private function _searchNode ($key, $pointer = NULL, $parentNode = NULL) {

        if (NULL == $pointer) {

            $pointer    = $this->_rootPointer();
            $parentNode = NULL;
        }

        $currentNode    = $this->_readNode($pointer, $parentNode);
        $value          = $currentNode->match($key);

        if ($this->_validateValue($value)) {

            return  $currentNode;
        }

        $childPointer   = $currentNode->matchChildren($key);

        if (!$this->_validatePointer($childPointer)) {

            return  $currentNode;
        }

        return  $this->_searchNode($key, $childPointer, $currentNode);
    }

    private function _validatePointer ($pointer) {

        return  is_int($pointer) && $pointer > 0;
    }

    private function _validateValue ($value) {

        return  is_int($value) && $value > 0;
    }

    private function _readNode ($pointer, $parentNode = NULL) {

        fseek($this->_fileHandle, $pointer);
        $content        = fread($this->_fileHandle, $this->_nodeSize());
        $pointerString  = substr($content, 0, $this->_pointerAreaSize());
        $pointerList    = unpack('l*', $pointerString);
        $keyString      = substr($content, $this->_pointerAreaSize(), $this->_keyAreaSize());
        $keyClips       = str_split($keyString, $this->_keyLength());
        $keyList        = array_filter(array_map('rtrim', $keyClips), array($this, '_filterKey'));
        $valueString    = substr($content, $this->_pointerAreaSize() + $this->_keyAreaSize(), $this->_valueAreaSize());
        $valueList      = array_filter(unpack('l*', $valueString), array($this, '_validateValue'));
        $data           = array_combine($keyList, $valueList);
        $node           = new BTreeNode($data, $pointerList, $parentNode, $pointer);

        return          $node;
    }

    private function _writeNode ($node) {

        $pointer    = $node->getPointer();

        if (BTreeNode::POINTER_NEW == $pointer) {

            fseek($this->_fileHandle, 0, SEEK_END);
            $pointer    = ftell($this->_fileHandle);

        } elseif (BTreeNode::POINTER_NEW_ROOT == $pointer) {

            fseek($this->_fileHandle, 0, SEEK_END);
            $pointer    = ftell($this->_fileHandle);
            $this->_options['root_pointer'] = $pointer;
            $this->_saveOptions();
            fseek($this->_fileHandle, 0, SEEK_END);

        } else {

            fseek($this->_fileHandle, $pointer);
        }

        $childrenPointer    = array_slice($node->children(), 0, $this->_numberChildren());
        $childrenContent    = str_pad(implode('', array_map(array($this, '_longPack'), $childrenPointer)), $this->_pointerAreaSize(), $this->_longPack(0));
        $keyContent         = str_pad(implode('', array_map(array($this, '_keyPad'), array_keys($node->data()))), $this->_keyAreaSize(), ' ');
        $valueContent       = str_pad(implode('', array_map(array($this, '_longPack'), $node->data())), $this->_valueAreaSize(), $this->_longPack(0));
        $content            = $childrenContent . $keyContent . $valueContent;

        fwrite($this->_fileHandle, $content);

        return  $pointer;
    }

    private function _filterKey ($key) {

        return  '' !== $key;
    }

    private function _longPack ($int) {

        return  pack('l', $int);
    }

    private function _keyPad ($key) {

        return  str_pad($key, $this->_keyLength(), ' ');
    }

    private function _nodeSize () {

        return  $this->_pointerAreaSize() +
                $this->_keyAreaSize() +
                $this->_valueAreaSize();
    }

    private function _pointerAreaSize () {

        return  ($this->_numberSlots() + 1) * $this->_longSize();
    }

    private function _keyAreaSize () {

        return  $this->_numberSlots() * $this->_keyLength();
    }

    private function _valueAreaSize () {

        return  $this->_numberSlots() * $this->_longSize();
    }

    private function _setOptions ($options) {

        $options        = $this->_defaultOptions($options);
        $optionsInFile  = $this->_readOptions();

        if ($optionsInFile) {

            $options    = $this->_decodeOptions($optionsInFile);
        }

        $this->_options = $options;
    }

    private function _defaultOptions ($options) {

        $default    = array(
            'number_slots'      => self::NUM_SLOTS,
            'key_length_max'    => self::KEY_LENGTH_MAX,
            'root_pointer'      => $this->_optionsLength(),
        );

        return      $options + $default;
    }

    private function _readOptions () {

        fseek($this->_fileHandle, 0);
        $options    = fread($this->_fileHandle, $this->_optionsLength());

        if ('' == $options) {

            return  false;
        }

        return  $options;
    }

    private function _saveOptions () {

        $string = $this->_encodeOptions($this->_options);
        fseek($this->_fileHandle, 0);
        fwrite($this->_fileHandle, $string);
    }

    private function _encodeOptions ($options) {

        return  pack(
            'l*',
            $options['number_slots'],
            $options['key_length_max'],
            $options['root_pointer']
        );
    }

    private function _decodeOptions ($content) {

        @list($null, $slots, $keyLength, $rootPointer)   = unpack('l*', $content);

        return  array(
            'number_slots'      => $slots,
            'key_length_max'    => $keyLength,
            'root_pointer'      => $rootPointer,
        );
    }

    private function _optionsLength () {

        return  $this->_longSize() * 3;
    }

    private function _longSize () {

        return  strlen(pack('l', 1));
    }

    private function _numberSlots () {

        return  $this->_options['number_slots'];
    }

    private function _numberChildren () {

        return  $this->_options['number_slots'] + 1;
    }

    private function _keyLength () {

        return  $this->_options['key_length_max'];
    }

    private function _rootPointer () {

        return  isset($this->_options['root_pointer'])
                ? $this->_options['root_pointer']
                : NULL;
    }

    private function _isFullNode (BTreeNode $node) {

        return  count($node->data()) >= $this->_numberSlots();
    }

    private function _isSeparateFirst () {

        return  $this->_numberSlots() % 2 == 1;
    }

    public  function dumpNode () {

        $pointer    = $this->_optionsLength();

        while(!feof($this->_fileHandle)) {

            $node       = $this->_readNode($pointer);
            var_dump($node);
            $pointer    += $this->_nodeSize();
        }
    }
}