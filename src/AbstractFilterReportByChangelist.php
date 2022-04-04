<?php

namespace Whotrades\PHPMDDiff;

abstract class AbstractFilterReportByChangelist
{
    abstract function __invoke(string $reportRaw, array $changes): string;
}
