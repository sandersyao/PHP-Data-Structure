<?php
/**
 * 梳排序
 */
class   Sort_Comb {

    const   SHRINK  = 1.3;

    static  public  function exec (Sort_SRC $src, $compare = NULL) {

        $compare    = is_callable($compare) ? $compare  : array(__CLASS__, 'compareDefault');
        self::_traverse($src, $compare);
    }

    private static  function _traverse (Sort_SRC $src, $compare) {

        $gap        = $count    = count($src);
        $swapped    = false;

        while (1 < $gap || true == $swapped) {

            $gap        = (int) floor($gap / self::SHRINK);
            $gap        = 1 > $gap  ? 1 : $gap;
            $swapped    = false;

            for ($offset = 0; $offset + $gap < $count; $offset ++) {

                $target = $offset + $gap;
                $result = call_user_func($compare, $src[$offset], $src[$target]);

                if ($result < 0) {

                    $src->swap($offset, $target);
                    $swapped    = true;
                }
            }
        }
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