<?php
/**
 * 快速排序 (分区交换排序)
 */
class   Sort_Quick {

    /**
     * 执行
     */
    static  public  function exec (Sort_SRC $src, $compare = NULL) {

        $compare    = is_callable($compare) ? $compare  : array(__CLASS__, 'compareDefault');

        self::_partition($src, $compare);
    }

    /**
     * 分区交换
     *
     * @param   Sort_SRC    $src            排序源
     * @param   callback    $compare        比较逻辑
     * @param   int         $offsetStart    起始位置
     * @param   int         $offsetEnd      结束位置
     */
    static  private function _partition (Sort_SRC $src, $compare, $offsetStart = 0, $offsetEnd = NULL) {

        $offsetEnd      = NULL === $offsetEnd   ? count($src) - 1   : $offsetEnd;

        if ($offsetEnd <= $offsetStart) {

            return  ;
        }

        $offsetPivot    = mt_rand($offsetStart, $offsetEnd);
        $pivot          = $src[$offsetPivot];
        $src->swap($offsetStart, $offsetPivot);

        for ($offset    = $offsetStore  = $offsetStart + 1; $offset <= $offsetEnd; $offset ++) {

            $result = call_user_func($compare, $src[$offset], $pivot);

            if ($result >= 0) {

                $src->swap($offset, $offsetStore);
                ++ $offsetStore;
            }
        }

        $src->swap($offsetStart, $offsetStore - 1);

        self::_partition($src, $compare, $offsetStart, $offsetStore - 2);
        self::_partition($src, $compare, $offsetStore, $offsetEnd);
    }

    /**
     * 默认比较逻辑
     *
     * @param   mixed   $a  源
     * @param   mixed   $b  排序判断方法
     */
    public  static  function compareDefault ($a, $b) {

        if ($a == $b) {

            return  0;
        }

        return  $a < $b ? 1 : -1;
    }
}