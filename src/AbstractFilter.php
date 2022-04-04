<?php

namespace Whotrades\PHPMDDiff;

abstract class AbstractFilter
{
    abstract function __invoke(string $reportRaw, array $changes): string;
}
