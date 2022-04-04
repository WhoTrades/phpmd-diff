<?php

namespace Whotrades\PHPMDDiff\Tests\Resource;

use Whotrades\PHPMDDiff\AbstractFilter;

class CustomFilter extends AbstractFilter
{
    public function execute(string $patch, string $report, string $pathPrefix): string
    {
        return 'OK';
    }
}
