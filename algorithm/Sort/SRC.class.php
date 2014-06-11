<?php
/**
 * 源
 */
class   Sort_SRC implements
    ArrayAccess,
    Countable {

    /**
     * 默认元素尺寸
     */
    const   SIZE_ELEMENT    = 4;

    /**
     * 默认值位置
     */
    const   POS_VALUE       = 0;

    /**
     * 默认值大小
     */
    const   SIZE_VALUE      = 4;

    /**
     * 默认值类型
     */
    const   TYPE_VALUE      = 'l';

    /**
     * 源数据
     */
    private $_src           = '';

    /**
     * 元素尺寸
     */
    private $_sizeElement   = self::SIZE_ELEMENT;

    /**
     * 值位置
     */
    private $_posValue      = self::POS_VALUE;

    /**
     * 值尺寸
     */
    private $_sizeValue     = self::SIZE_VALUE;

    /**
     * 值类型
     */
    private $_typeValue     = self::TYPE_VALUE;

    /**
     * 方向
     */
    private $_derict        = 1;

    /**
     * 构造函数
     *
     * @param   string  $src        源数据
     * @param   array   $options    配置
     */
    public  function __construct ($src = '', $options = array()) {

        $this->_src     = $src;
        $this->_options = $options;
    }

    /**
     * 逆序
     */
    public  function reverse () {

        $this->_derict *= -1;
    }

    /**
     * 清空
     */
    public  function clean () {

        $this->_src = '';
    }

    /**
     * 交换
     *
     * @param   int $offsetA    第一个下标
     * @param   int $offsetB    第二个下标
     */
    public  function swap ($offsetA, $offsetB) {

        if ($offsetA == $offsetB) {

            return  ;
        }

        $valueA = $this->offsetGet($offsetA);
        $valueB = $this->offsetGet($offsetB);
        $valueA ^= $valueB;
        $valueB ^= $valueA;
        $valueA ^= $valueB;
        $this->offsetSet($offsetA, $valueA);
        $this->offsetSet($offsetB, $valueB);
    }

    public  function offsetExists ($offset) {

        return  strlen($this->_getElement($offset)) == $this->_sizeElement;
    }

    public  function offsetGet ($offset) {

        $element    = $this->_getElement($offset);

        return      $this->_getValue($element);
    }

    public  function offsetSet ($offset, $value) {

        $offset     = NULL === $offset  ? $this->count()    : $offset;
        $element    = $this->_getElement($offset);
        $element    = false === $element    ? str_repeat(' ', $this->_sizeElement)  : $element;
        $element    = $this->_setValue($element, $value);
        $this->_setElement($offset, $element);
    }

    public  function offsetUnset ($offset) {

        $this->_src = $this->_derict > 0
                    ? substr_replace($this->_src, '', $offset * $this->_sizeElement * $this->_derict, $this->_sizeElement)
                    : substr_replace($this->_src, '', ($offset + 1) * $this->_sizeElement * $this->_derict, $this->_sizeElement);
    }

    public  function count () {

        return  (int) floor(strlen($this->_src) / $this->_sizeElement);
    }

    public  function __toString () {

        $str    = '[';

        for ($offset = 0; $offset < $this->count(); $offset ++) {

            $str    .= 0 == $offset ? ''    : ',';
            $str    .= $this->offsetGet($offset);
        }

        return  $str . ']';
    }

    private function _setElement ($offset, $element) {

        if ($this->_derict > 0) {

            $this->_replace($offset * $this->_sizeElement * $this->_derict, $element);
        } else {

            $this->_replace(($offset + 1) * $this->_sizeElement * $this->_derict, $element);
        }
    }

    private function _getElement ($offset) {

        return  $this->_derict > 0
                ? substr($this->_src, $offset * $this->_sizeElement * $this->_derict, $this->_sizeElement)
                : substr($this->_src, ($offset + 1) * $this->_sizeElement * $this->_derict, $this->_sizeElement);
    }

    private function _getValue ($element) {

        @list($null, $value)   = unpack($this->_typeValue, substr($element, $this->_posValue, $this->_sizeValue));

        return                  $value;
    }

    private function _setValue ($element, $value) {

        $pack   = pack($this->_typeValue, $value);

        return  substr_replace($element, $pack, $this->_posValue, $this->_sizeValue);
    }

    private function _setOptions ($options) {

        if (isset($options['size_element'])) {

            $this->_sizeElement = $options['size_element'];
        }

        if (isset($options['pos_value'])) {

            $this->_posValue = $options['pos_value'];
        }

        if (isset($options['size_value'])) {

            $this->_sizeValue = $options['size_value'];
        }

        if (isset($options['type_Value'])) {

            $this->_typeValue = $options['type_Value'];
        }
    }

    private function _replace ($position, $replacement) {

        $posPrev    = $position - 1;

        while ($posPrev > 0 && false === isset($this->_src[$posPrev])) {

            $this->_src[strlen($this->_src)]    = ' ';
        }

        for ($loop = 0, $offset = $position;$loop < strlen($replacement);$loop ++, $offset ++) {

            if (isset($this->_src[$offset])) {

                $this->_src[$offset]    = $replacement[$loop];
            } else {

                $this->_src .= $replacement[$loop];
            }
        }
    }
}