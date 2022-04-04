# phpmd-diff
**phpmd-diff** is a tool for creating PHPMD reports, cleared of violations unrelated to the modified lines.

## Installation

```bash
composer require-dev whotrades/phpmd-diff
```

## Usage

```bash
$ git diff HEAD^1 > /path/to/patch.txt
$ /path/to/phpmd /path/to/sources xml /path/to/rulesets.xml --reportfile report.xml
$ /path/to/phpmd-diff --path-prefix=/custom/path/prefix --filter=xml /path/to/phpmd/report.xml /path/to/patch.txt 1> /path/to/a/cleaned/report.xml
```