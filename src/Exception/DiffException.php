<?php

declare(strict_types=1);

namespace Whotrades\PHPMDDiff\Exception;

use Exception;

class DiffException extends Exception
{
    public const ERR_LOAD_FILE = 100;

    public const ERR_LOAD_REPORT = 200;

    public const ERR_LOAD_DIFF = 300;

    public const ERR_CUSTOM_FILTER = 400;
}
