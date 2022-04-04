<?php

namespace Whotrades\PHPMDDiff\Filter;

use DOMDocument;
use DOMNode;
use Whotrades\PHPMDDiff\Exception\DiffException;

class FilterXMLReportByChangelist extends \Whotrades\PHPMDDiff\AbstractFilter
{
    /**
     * @param string $reportRaw
     * @param array $changes
     *
     * @return string
     *
     * @throws DiffException
     */
    public function __invoke(string $reportRaw, array $changes): string
    {
        $report = new DOMDocument();
        $report->preserveWhiteSpace = false;
        $report->formatOutput = true;
        if (!@$report->loadXML($reportRaw)) {
            throw new DiffException("Can't load PHPMD report.", DiffException::ERR_LOAD_REPORT);
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
        return $report->saveXML();
    }
}
