<?php

namespace Whotrades\PHPMDDiff\Tests\Resource;

use Whotrades\PHPMDDiff\AbstractFilterReportByChangelist;

class CustomFilter extends AbstractFilterReportByChangelist
{
    public function __invoke(string $reportRaw, array $changes): string
    {
        return 'OK';
    }
}
