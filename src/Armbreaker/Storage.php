<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;


use CharlotteDunois\Collect\Collection;
use JsonSerializable;

abstract class Storage extends Collection implements JsonSerializable
{

    public function jsonSerialize()
    {
        return $this->all();
    }
}
