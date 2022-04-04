<?php

declare(strict_types=1);

namespace Whotrades\PHPMDDiff;

use Whotrades\PHPMDDiff\Exception\DiffException;
use Whotrades\PHPMDDiff\Filter\XMLFilter;

final class Diff
{
    public function execute(string $phpmdFile, string $patchFile, string $pathPrefix, string $filterType): string
    {
        $patchRaw = @file_get_contents($patchFile);

        if (empty($patchRaw)) {
            throw new DiffException("Can't load patch file.", DiffException::ERR_LOAD_FILE);
        }

        $reportRaw = file_get_contents($phpmdFile);
        if (!$reportRaw) {
            throw new DiffException("Can't load PHPMD report.", DiffException::ERR_LOAD_FILE);
        }

        switch ($filterType) {
            case 'xml':
                $filter = new XMLFilter();
                break;
            default:
                if (!class_exists($filterType, true)) {
                    throw new DiffException(sprintf("Custom filter class '%s' not found", $filterType), DiffException::ERR_CUSTOM_FILTER);
                }

                if (!is_a($filterType, AbstractFilter::class, true)) {
                    throw new DiffException(sprintf("Custom filter class should extend '%s'", AbstractFilter::class), DiffException::ERR_CUSTOM_FILTER);
                }

                $filter = new $filterType();
        }

        return $filter->execute($patchRaw, $reportRaw, $pathPrefix);
    }

}
