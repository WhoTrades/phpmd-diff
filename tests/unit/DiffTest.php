<?php

declare(strict_types=1);

namespace Whotrades\PHPMDDiff\Tests\Unit;

use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\TestCase;
use Whotrades\PHPMDDiff\Diff;
use Whotrades\PHPMDDiff\Exception\DiffException;

class DiffTest extends TestCase
{
    /** @var vfsStreamDirectory */
    private $vfs;
    /** @var Diff */
    private $diff;

    public function setUp(): void
    {
        $this->diff = new Diff();
        $this->vfs = vfsStream::setup('root');
        $this->vfs->addChild(
            vfsStream::newFile('diff.txt')->withContent(file_get_contents(__DIR__ . '/../resources/diff.txt'))
        );
        $this->vfs->addChild(
            vfsStream::newFile('report.xml')->withContent(file_get_contents(__DIR__ . '/../resources/report.xml'))
        );
    }

    /**
     * @throws DiffException
     */
    public function testReportFileCannotBeLoaded()
    {
        $this->expectException(DiffException::class);
        $this->expectExceptionCode(DiffException::ERR_LOAD_FILE);

        $this->vfs->addChild(
            vfsStream::newFile('report_broken.xml')->withContent('<xml BR0K3N />')
        );
        $this->diff->execute($this->vfs->getChild('report_broken.xml')->url(), $this->vfs->getChild('diff.txt')->url(), '');
    }

    /**
     * @throws DiffException
     */
    public function testDiffFileCannotBeLoaded()
    {
        $this->expectException(DiffException::class);
        $this->expectExceptionCode(DiffException::ERR_LOAD_FILE);

        $this->vfs->addChild(
            vfsStream::newFile('diff_broken.txt')->withContent('#$%DSFGSD')
        );
        $this->diff->execute(
            $this->vfs->getChild('report.xml')->url(),
            $this->vfs->getChild('diff_broken.txt')->url(),
            ''
        );
    }

    /**
     * @throws DiffException
     */
    public function testCorrectOutput()
    {
        $dom = $this->diff->execute(
            $this->vfs->getChild('report.xml')->url(),
            $this->vfs->getChild('diff.txt')->url(),
            '/my/custom/path/prefix/'
        );

        // We should have only 1 `file` node left
        $this->assertEquals(1, $dom->getElementsByTagName('file')->count());
        // We should have only 1 `violation` node left
        $violations = $dom->getElementsByTagName('violation');
        $this->assertEquals(1, $violations->count());
        // And it should be the `LongClassName` rule violation
        $this->assertEquals('LongClassName', (string) $violations->item(0)->attributes->getNamedItem('rule')->nodeValue);
        // And we should have other nodes left `as is`
        $this->assertEquals(2, $dom->getElementsByTagName('error')->count());
    }
}
