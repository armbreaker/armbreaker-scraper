<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;


use Carbon\Carbon;
use LogicException;
use QueryPath\DOMQuery;

trait ScraperUtilsTrait
{


    /**
     * Spacebattles sends us dates in...weird formats. Standardize and parse them.
     *
     * @param DOMQuery $qp
     *
     * @return Carbon
     */
    protected function unfuckDates(DOMQuery $qp): ?Carbon
    {
        try {
            if ($qp->is("span")) {
                $obj = new Carbon(str_replace(" at", "", $qp->text()), "America/New_York");
            } elseif ($qp->is("abbr")) {
                $obj = new Carbon(date('c', $qp->attr("data-time")));
            } else {
                throw new LogicException("what the fuck");
            }
            $obj->setTimezone("UTC");
            return $obj;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Turn a messy username/string into a nice integer
     *
     * @param string $uid
     *
     * @return int
     */
    protected function unfuckUserID(string $uid): int
    {
        $matches = [];
        preg_match("/\\.(\\d+)\\//i", $uid, $matches);
        if (mb_strlen($matches[1]) > 0 && is_numeric($matches[1])) {
            return (int) $matches[1];
        }
    }
}
