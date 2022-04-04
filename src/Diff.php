<?php

declare(strict_types=1);

namespace Whotrades\PHPMDDiff;

use SebastianBergmann\Diff\Line;
use SebastianBergmann\Diff\Parser;
use Whotrades\PHPMDDiff\Exception\DiffException;
use Whotrades\PHPMDDiff\Filter\FilterXMLReportByChangelist;

final class Diff
{
    /**
     * @param string $phpmdFile
     * @param string $patchFile
     * @param string $pathPrefix
     * @param string $filterType
     *
     * @return string
     *
     * @throws DiffException
     */
    public function execute(string $phpmdFile, string $patchFile, string $pathPrefix, string $filterType): string
    {
        $patchRaw = @file_get_contents($patchFile);

        if (empty($patchRaw)) {
            throw new DiffException("Can't load patch file.", DiffException::ERR_LOAD_FILE);
        }

        $changes = $this->getChangesFromPatch($patchRaw, $pathPrefix);

        $reportRaw = file_get_contents($phpmdFile);
        if (empty($reportRaw)) {
            throw new DiffException("Can't load PHPMD report.", DiffException::ERR_LOAD_FILE);
        }

        switch ($filterType) {
            case 'xml':
                return (new FilterXMLReportByChangelist())($reportRaw, $changes);
            default:
                if (!class_exists($filterType, true)) {
                    throw new DiffException(sprintf("Custom filter class '%s' not found", $filterType), DiffException::ERR_CUSTOM_FILTER);
                }

                if (!is_a($filterType, AbstractFilter::class, true)) {
                    throw new DiffException(sprintf("Custom filter class should extend '%s'", AbstractFilter::class), DiffException::ERR_CUSTOM_FILTER);
                }

                return (new $filterType())($reportRaw, $changes);
        }
    }

    /**
     * @param string $patchRaw
     * @param string $pathPrefix
     *
     * @return array
     *
     * @throws DiffException
     */
    protected function getChangesFromPatch(string $patchRaw, string $pathPrefix): array
    {
        if (empty($patch = (new Parser())->parse($patchRaw))) {
            throw new DiffException("Can't load patch file.", DiffException::ERR_LOAD_DIFF);
        }

        if (substr($pathPrefix, -1, 1) !== DIRECTORY_SEPARATOR) {
            $pathPrefix .= DIRECTORY_SEPARATOR;
        }

        $changes = [];
        foreach ($patch as $diff) {
            $file = $pathPrefix . substr($diff->getTo(), 2);
            $changes[$file] = [];

            foreach ($diff->getChunks() as $chunk) {
                $lineNo = $chunk->getEnd();

                foreach ($chunk->getLines() as $line) {
                    if ($line->getType() === Line::ADDED) {
                        $changes[$file][] = $lineNo;
                    }

                    if ($line->getType() !== Line::REMOVED) {
                        ++$lineNo;
                    }
                }
            }
        }

        return $changes;
    }
}
