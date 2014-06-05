<?php
/**
 * 插入排序
 */
class   Sort_Insertion {

    /**
     * 插入排序
     *
     * @param   Sort_SRC    $src        源
     * @param   callback    $compare    排序判断方法
     */
    public  static  function exec (Sort_SRC $src, $compare = NULL) {

        $compare    = is_callable($compare) ? $compare  : array(__CLASS__, 'compareDefault');

        for ($offset = 1; $offset < count($src); $offset ++) {

            $target = $offset - 1;
            $value  = $src[$offset];

            while (0 <= $target) {

                $result = call_user_func($compare, $src[$target], $value);

                if ($result >= 0) {

                    $src[$target + 1]   = $value;
                    break;
                }

                $src[$target + 1]   = $src[$target];

                if (0 == $target) {

                    $src[$target]   = $value;
                    break;
                }

                -- $target;
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