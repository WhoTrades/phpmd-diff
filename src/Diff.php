<?php

declare(strict_types=1);

namespace Whotrades\PHPMDDiff;

use DOMDocument;
use DOMNode;
use SebastianBergmann\Diff\Line;
use SebastianBergmann\Diff\Parser;
use Whotrades\PHPMDDiff\Exception\DiffException;

final class Diff
{
    public function execute(string $phpmdFile, string $patchFile, string $pathPrefix): DOMDocument
    {
        $patchRaw = @file_get_contents($patchFile);
        if (empty($patchRaw) || empty($patch = (new Parser())->parse($patchRaw))) {
            throw new DiffException("Can't load patch file.", DiffException::ERR_LOAD_FILE);
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

        $report = new DOMDocument();
        $report->preserveWhiteSpace = false;
        $report->formatOutput = true;
        if (!@$report->load($phpmdFile)) {
            throw new DiffException("Can't load PHPMD report.", DiffException::ERR_LOAD_FILE);
        }


        $root = $report->documentElement;
        $files = $root->getElementsByTagName('file');

        $filesToRemove = [];
        $violationsToRemove = [];
        /** @var DOMNode $file */
        foreach ($files as $file) {
            $fileName = (string) $file->attributes->getNamedItem('name')->nodeValue;
            // If we haven't such file in our diff we can remove it right now.
            if (!isset($changes[$fileName])) {
                $filesToRemove[] = $file;
                continue;
            }

            $violationNodeCount = 0;
            /** @var DOMNode $violation */
            foreach ($file->childNodes as $violation) {
                if ('violation' !== $violation->nodeName) {
                    continue;
                }

                $violationsLineRage = range(
                    (int) $violation->attributes->getNamedItem('beginline')->nodeValue,
                    (int) $violation->attributes->getNamedItem('endline')->nodeValue
                );
                if (empty(array_intersect($violationsLineRage, $changes[$fileName]))) {
                    $violationsToRemove[] = $violation;
                } else {
                    ++$violationNodeCount;
                }
            }

            // If all `violation` child nodes were removed we also should remove parent node.
            if (0 == $violationNodeCount) {
                $filesToRemove[] = $file;
            }
        }

        foreach ($violationsToRemove as $violation) {
            $violation->parentNode->removeChild($violation);
        }
        foreach ($filesToRemove as $file) {
            $file->parentNode->removeChild($file);
        }

        $report->normalizeDocument();
        return $report;
    }

}
