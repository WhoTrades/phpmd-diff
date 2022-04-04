<?php

namespace Whotrades\PHPMDDiff;

use SebastianBergmann\Diff\Line;
use SebastianBergmann\Diff\Parser;
use Whotrades\PHPMDDiff\Exception\DiffException;

abstract class AbstractFilter
{
    abstract function execute(string $patch, string $report, string $pathPrefix): string;

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
