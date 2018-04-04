<?php

namespace iqb;

use PHPUnit\Framework\TestCase;

class ZipArchiveTest extends TestCase
{
    private $zipFileNoExtras = 'test-no-extras.zip';
    private $zipFileComments = 'comments.zip';


    public function setUp()
    {
        parent::setUp();

        // DOS date+time is stored as a local timezone variable, not UTC
        // libzip uses TZ environment variable to get the timezone whereas this package uses the PHP internal date timezone
        // Set both to UTC to avoid test problems system timezone settings
        \date_default_timezone_set('UTC');
        \putenv("TZ=UTC");
    }


    /**
     * Helper function for data providers: generate an Iterator that returns an entry for file from the supplied ZIP file.
     * Entry-format: "$index: $name" => [$fileName, $index, $name]
     *
     * @param string $fileName
     * @return \Generator
     */
    private function fileEntryLister(string $fileName)
    {
        $fromExt = new \ZipArchive();
        $fromExt->open($fileName);

        for ($i=0; $i<$fromExt->numFiles; $i++) {
            $name = $fromExt->statIndex($i)['name'];
            yield "$i: $name" => [$fileName, $i, $name];
        }
    }


    /**
     * Compare the result of method calls on the extensions ZipArchive class and this package's implementation.
     * The result of the methods is compared for each parameter list from the $parameterLists array.
     *
     * @param string $filename ZIP file to test on
     * @param string $testMethod The method on the ZipArchive to compare results for
     * @param array $parameterLists List of parameter lists
     * @param bool $useSame Whether to use assertSame or assertEquals to compare results
     */
    private function compareMethodResults(string $filename, string $testMethod, array $parameterLists, bool $useSame)
    {
        $fromExt = new \ZipArchive();
        $fromExt->open($filename);

        $fromPkg = new ZipArchive();
        $fromPkg->open($filename);

        $assertMethod = ($useSame ? 'assertSame' : 'assertEquals');

        foreach ($parameterLists as $parameterList) {
            $this->{$assertMethod}($fromExt->{$testMethod}(...$parameterList), $fromPkg->{$testMethod}(...$parameterList), new ErrorMessage($testMethod, ...$parameterList));
        }
    }


    /**
     * Create parameter lists from $name, building unchanged/lowercase/uppercase/basename and flag combinations to pass to compareMethodResults()
     */
    private function createNameAndFlagsParameterLists(string $name)
    {
        $nameL = \strtolower($name);
        $nameU = \strtoupper($name);
        $basename = \basename($name);
        $basenameL = \strtolower($basename);
        $basenameU = \strtoupper($basename);

        return [
            [$name],

            [$name, \ZipArchive::FL_NOCASE],
            [$nameL, \ZipArchive::FL_NOCASE],
            [$nameU, \ZipArchive::FL_NOCASE],

            [$name, \ZipArchive::FL_NODIR],
            [$basename, \ZipArchive::FL_NODIR],

            [$name, \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE],
            [$nameL, \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE],
            [$nameU, \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE],

            [$basename, \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE],
            [$basenameL, \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE],
            [$basenameU, \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE],
        ];
    }


    public function commentsZipFileProvider()
    {
        return $this->fileEntryLister($this->zipFileComments);
    }


    public function noExtrasZipFileProvider()
    {
        return $this->fileEntryLister($this->zipFileNoExtras);
    }


    public function testZipArchive()
    {
        $fromExt = new \ZipArchive();
        $fromExt->open($this->zipFileNoExtras);

        $fromPkg = new ZipArchive();
        $fromPkg->open($this->zipFileNoExtras);

        $this->assertSame($fromExt->numFiles, $fromPkg->numFiles);
        $this->assertSame($fromExt->comment, $fromPkg->comment);
    }


    public function testGetArchiveComment()
    {
        $fromExt = new \ZipArchive();
        $fromExt->open($this->zipFileComments);

        $fromPkg = new ZipArchive();
        $fromPkg->open($this->zipFileComments);

        $this->assertSame($fromExt->getArchiveComment(), $fromPkg->getArchiveComment());
    }


    /**
     * Tests getCommentIndex() on unmodified zip file.
     * @dataProvider commentsZipFileProvider
     */
    public function testGetCommentIndex(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'getCommentIndex', [[$index]], true);
    }


    /**
     * Tests getCommentName() on unmodified zip file.
     * @dataProvider commentsZipFileProvider
     */
    public function testGetCommentName(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'getCommentName', $this->createNameAndFlagsParameterLists($name), true);
    }


    /**
     * Tests statName() on unmodified zip file.
     * @dataProvider noExtrasZipFileProvider
     */
    public function testLocateNameUnmodified(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'locateName', $this->createNameAndFlagsParameterLists($name), true);
    }


    /**
     * Tests statIndex() on unmodified zip file.
     * @dataProvider noExtrasZipFileProvider
     */
    public function testStatUnmodified(string $fileName, int $index, string $name)
    {
        $fromExt = new \ZipArchive();
        $fromExt->open($fileName);

        $fromPkg = new ZipArchive();
        $fromPkg->open($fileName);

        $this->assertEquals($fromExt->statIndex($index), $fromPkg->statIndex($index), "Testing: $name");
    }


    /**
     * Tests statName() on unmodified zip file.
     * @dataProvider noExtrasZipFileProvider
     */
    public function testStatNameUnmodified(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'statName', $this->createNameAndFlagsParameterLists($name), true);
    }
}
