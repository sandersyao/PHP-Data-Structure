<?php
/**
 * 源
 */
class   Sort_SRC implements
    ArrayAccess,
    Countable {

    const   SIZE_ELEMENT    = 4;
    const   POS_VALUE       = 0;
    const   SIZE_VALUE      = 4;
    const   TYPE_VALUE      = 'l';

    private $_src           = '';

    private $_sizeElement   = self::SIZE_ELEMENT;

    private $_posValue      = self::POS_VALUE;

    private $_sizeValue     = self::SIZE_VALUE;

    private $_typeValue     = self::TYPE_VALUE;

    public  function __construct ($src = '', $options = array()) {

        $this->_src = $src;
        $this->_options = $options;
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

        return  ;
    }

    public  function count () {

        return  (int) floor(strlen($this->_src) / $this->_sizeElement);
    }

    private function _setElement ($offset, $element) {

        $this->_replace($offset * $this->_sizeElement, $element);
    }

    private function _getElement ($offset) {

        return  substr($this->_src, $offset * $this->_sizeElement, $this->_sizeElement);
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