<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker\Model;


use Armbreaker\Storage;

class FicStorage extends Storage
{

    public function get(int $key): ?Fic
    {
        return parent::get($key);
    }

    public function set(int $key, Fic $value)
    {
        parent::set($key, $value);
        return $this;
    }
}
