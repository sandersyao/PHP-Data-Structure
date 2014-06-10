<?php
/**
 * 合并排序
 */
class   Sort_Merge {

    /**
     * 执行
     */
    public  static  function exec (Sort_SRC $src, $compare = NULL) {

        $compare    = is_callable($compare) ? $compare  : array(__CLASS__, 'compareDefault');
        $splite     = self::_spliteNatural($src, $compare);
        self::_merge($src, $splite, $compare);
    }

    /**
     * 差分 自然顺序
     */
    private static  function _spliteNatural (Sort_SRC $src, $compare) {

        $splite = new Sort_SRC;
        $status = 1;

        for ($offset = 1; $offset < count($src); $offset ++) {

            $result = call_user_func($compare, $src[$offset - 1], $src[$offset]);

            if ($result != 0 && $result != $status) {

                $status     = $result;
                $splite[]   = $offset;
            }
        }

        return  $splite;
    }

    /**
     * 合并
     */
    private static  function _merge (Sort_SRC $src, Sort_SRC $splite, $compare) {

        if (0 == count($splite)) {

            return  $src;
        }

        $listLeft   = new Sort_SRC;
        $listRight  = new Sort_SRC;
        $status     = 1;

        for ($offsetStart   = $offsetSrc    = $offsetSplite = 0; $offsetSrc < count($src); $offsetSrc ++) {

            if ($offsetSrc == $splite[$offsetSplite]) {

                if (0 > $status || $offsetSplite == count($splite)) {

                    self::_combine($src, $compare, $offsetStart, $listLeft, $listRight);
                    $offsetStart    = $offsetSrc;
                    $listLeft->clean();
                    $listRight->clean();

                    if (isset($splite[$offsetSplite - 1])) {

                        unset($splite[$offsetSplite - 1]);
                    }

                    -- $offsetSplite;
                }

                ++ $offsetSplite;
                $status *= -1;
            }

            if (0 < $status) {

                $listLeft[] = $src[$offsetSrc];
            }

            if (0 > $status) {

                $listRight[]    = $src[$offsetSrc];
            }
        }

        if (0 > $status || 0 < count($listRight)) {

            self::_combine($src, $compare, $offsetStart, $listLeft, $listRight);
            $offsetStart    = $offsetSrc;

            if (isset($splite[$offsetSplite - 1])) {

                unset($splite[$offsetSplite - 1]);
            }
        }

        self::_merge($src, $splite, $compare);
    }

    private static  function _combine (Sort_SRC $src, $compare, $start, Sort_SRC $left, Sort_SRC $right) {

        self::_initializeOrder($left, $compare);
        self::_initializeOrder($right, $compare);

        for ($offsetSrc = $offsetLeft = $offsetRight = 0; $offsetLeft < count($left) && $offsetRight < count($right); $offsetSrc ++) {

            $result = call_user_func($compare, $left[$offsetLeft], $right[$offsetRight]);

            if ($result >= 0) {

                $src[$offsetSrc + $start]   = $left[$offsetLeft];
                ++ $offsetLeft;
            } else {

                $src[$offsetSrc + $start]   = $right[$offsetRight];
                ++ $offsetRight;
            }
        }

        if ($offsetLeft < count($left)) {

            for (; $offsetLeft < count($left); $offsetLeft ++, $offsetSrc ++) {

                $src[$offsetSrc + $start]   = $left[$offsetLeft];
            }
        }

        if ($offsetRight < count($right)) {

            for (; $offsetRight < count($right); $offsetRight ++, $offsetSrc ++) {

                $src[$offsetSrc + $start]   = $right[$offsetRight];
            }
        }
    }

    private static  function _initializeOrder (Sort_SRC $list, $compare) {

        $count  = count($list);

        if ($count > 0 && 0 > call_user_func($compare, $list[0], $list[$count - 1])) {

            $list->reverse();
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