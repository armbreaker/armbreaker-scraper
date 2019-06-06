<?php
/**
 * Copyright (c) 2019 Armbreaker Developers.
 * Use of this source code is governed by the MIT license, which
 * can be found in the LICENSE file.
 */

namespace Armbreaker;


interface QueueInterface
{
    public function getPendingJobCount(): int;

    public function getJob(bool $claim = true): ?WorkUnitInterface;

    public function completeJob(WorkUnitInterface $job);

    public function addJob(WorkUnitInterface $job);
}
