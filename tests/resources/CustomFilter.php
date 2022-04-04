<?php

namespace Whotrades\PHPMDDiff\Tests\Resource;

use Whotrades\PHPMDDiff\AbstractFilter;

class CustomFilter extends AbstractFilter
{
    public function __invoke(string $reportRaw, array $changes): string
    {
        return 'OK';
    }
}
