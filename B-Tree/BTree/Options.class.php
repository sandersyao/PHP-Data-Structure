<?php
/**
 * 配置参数模型
 */
class   BTree_Options {

    /**
     * 键数量
     */
    const   NUM_SLOTS       = 3;

    /**
     * 键长度
     */
    const   KEY_LENGTH_MAX  = 10;

    /**
     * 关键词数量
     */
    private $_numberSlots;

    /**
     * 关键词长度
     */
    private $_lengthKey;

    /**
     * 创建数据
     *
     * @param   array   $data   配置数据
     */
    public  static  function create ($data) {

        return  new self($data);
    }

    /**
     * 构造函数
     *
     * @param   array   $data   配置数据
     */
    public  function __construct ($data) {

        $data               = $this->_default($data);
        $this->_numberSlots = $data['number_slots'];
        $this->_lengthKey   = $data['length_key'];
    }

    /**
     * 默认配置混合
     *
     * @param   array   $data   配置数据
     * @return  array           混合后的配置数据
     */
    private function _default ($data) {

        $default    = array(
            'number_slots'  => self::NUM_SLOTS,
            'length_key'    => self::KEY_LENGTH_MAX,
        );

        return      $data + $default;
    }

    /**
     * 关键词最大数量
     *
     * @return  int 关键词最大数量
     */
    public  function numberSlots ($numberSlots = NULL) {

        $numberSlotsOld = $this->_numberSlots;

        if (is_int($numberSlots)) {

            $this->_numberSlots = (int) $numberSlots;
        }

        return          $numberSlotsOld;
    }

    /**
     * 子节点最大数量
     *
     * @return  int 子节点最大数量
     */
    public  function numberChildren () {

        return  $this->numberSlots() + 1;
    }

    /**
     * 关键词长度
     *
     * @return  int 关键词长度
     */
    public  function keyLength ($lengthKey = NULL) {

        $lengthKeyOld   = $this->_lengthKey;

        if (is_int($lengthKey)) {

            $this->_lengthKey   = (int) $lengthKey;
        }

        return          $lengthKeyOld;
    }
}