<?php

namespace iqb;

use PHPUnit\Framework\TestCase;

class ZipArchiveTest extends TestCase
{
    private $zipFileNoExtras = __DIR__ . '/test-no-extras.zip';
    private $zipFileComments = __DIR__ . '/comments.zip';
    private $zipFileNoComment = __DIR__ . '/nocomment.zip';


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
     * @param string $assertMethod The method of $this used as the assertion method
     */
    private function compareMethodResults(string $filename, string $testMethod, array $parameterLists, string $assertMethod = 'assertSame')
    {
        $fromExt = new \ZipArchive();
        $fromExt->open($filename);

        $fromPkg = new ZipArchive();
        $fromPkg->open($filename);

        foreach ($parameterLists as $parameterList) {
            $this->{$assertMethod}($fromExt->{$testMethod}(...$parameterList), $fromPkg->{$testMethod}(...$parameterList), new ErrorMessage($testMethod, $parameterList, false));

            $this->assertSame($fromExt->numFiles, $fromPkg->numFiles, new ErrorMessage('ZipArchive::$numFiles after '.$testMethod, $parameterList, false));
            $this->assertSame($fromExt->status, $fromPkg->status, new ErrorMessage('ZipArchive::$status after '.$testMethod, $parameterList, false));
            $this->assertSame($fromExt->statusSys, $fromPkg->statusSys, new ErrorMessage('ZipArchive::$statusSys after '.$testMethod, $parameterList, false));
            $this->assertSame($fromExt->filename, $fromPkg->filename, new ErrorMessage('ZipArchive::$filename after '.$testMethod, $parameterList, false));
            $this->assertSame($fromExt->comment, $fromPkg->comment, new ErrorMessage('ZipArchive::$comment after '.$testMethod, $parameterList, false));
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

    /**
     * @throws \ReflectionException
     */
    public function constantsProvider()
    {
        $reflectionClass = new \ReflectionClass(\ZipArchive::class);

        /* @var $constant \ReflectionClassConstant */
        foreach ($reflectionClass->getConstants() as $constantName => $constantValue) {
            yield "$constantName ($constantValue)" => [$constantName, $constantValue];
        }
    }

    /**
     * @dataProvider constantsProvider
     */
    public function testConstants(string $name)
    {
        $this->assertTrue(\defined(ZipArchive::class . '::' . $name), 'Missing constant ZipArchive::' . $name);
        $this->assertSame(\constant(\ZipArchive::class . '::' . $name), \constant(ZipArchive::class . '::' . $name), 'Checking constant ZipArchive::' . $name);
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
        $myComment = "Foobar";
        $files = [$this->zipFileComments, $this->zipFileNoComment];

        foreach ($files as $filename) {
            $fromExt = new \ZipArchive();
            $fromExt->open($filename);

            $fromPkg = new ZipArchive();
            $fromPkg->open($filename);

            $this->assertSame($fromExt->comment, $fromPkg->comment);
            $this->assertSame($fromExt->getArchiveComment(), $fromPkg->getArchiveComment());
            $this->assertSame($fromExt->getArchiveComment(\ZipArchive::FL_UNCHANGED), $fromPkg->getArchiveComment(\ZipArchive::FL_UNCHANGED));

            $fromExt->comment = $myComment;
            $fromPkg->comment = $myComment;

            $this->assertSame($fromExt->comment, $fromPkg->comment);
            $this->assertSame($fromExt->getArchiveComment(), $fromPkg->getArchiveComment());
            $this->assertSame($fromExt->getArchiveComment(\ZipArchive::FL_UNCHANGED), $fromPkg->getArchiveComment(\ZipArchive::FL_UNCHANGED));
            $this->assertSame($fromExt->status, $fromPkg->status);
            $this->assertSame($fromExt->statusSys, $fromPkg->statusSys);
        }
    }


    /**
     * Tests getCommentIndex() on unmodified zip file.
     * @dataProvider commentsZipFileProvider
     */
    public function testGetCommentIndex(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'getCommentIndex', [[$index]], 'assertSame');
    }


    /**
     * Tests getCommentName() on unmodified zip file.
     * @dataProvider commentsZipFileProvider
     */
    public function testGetCommentName(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'getCommentName', $this->createNameAndFlagsParameterLists($name), 'assertSame');
    }


    /**
     * @dataProvider noExtrasZipFileProvider
     */
    public function testGetFromIndex(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'getFromIndex', [
            [$index],
            [$index, null, \ZipArchive::FL_COMPRESSED],
            [$index, 0],
            [$index, 0, \ZipArchive::FL_COMPRESSED],
            [$index, 64],
            [$index, 64, \ZipArchive::FL_COMPRESSED],
            [$index, 600],
            [$index, 600, \ZipArchive::FL_COMPRESSED],
            [$index, 100000],
            [$index, 100000, \ZipArchive::FL_COMPRESSED],
        ], 'assertEquals');
    }


    /**
     * @dataProvider noExtrasZipFileProvider
     */
    public function testGetFromName(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'getFromName', [
            [$name],
            [$name, null, \ZipArchive::FL_COMPRESSED],
            [$name, 0],
            [$name, 0, \ZipArchive::FL_COMPRESSED],
            [$name, 64],
            [$name, 64, \ZipArchive::FL_COMPRESSED],
            [$name, 600],
            [$name, 600, \ZipArchive::FL_COMPRESSED],
            [$name, 100000],
            [$name, 100000, \ZipArchive::FL_COMPRESSED],
        ], 'assertEquals');
    }


    /**
     * Tests statName() on unmodified zip file.
     * @dataProvider noExtrasZipFileProvider
     */
    public function testLocateNameUnmodified(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'locateName', $this->createNameAndFlagsParameterLists($name), 'assertSame');
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

        $refStat = $fromExt->statIndex($index);
        $pkgStat = $fromPkg->statIndex($index);

        $this->assertStat($refStat, $pkgStat, "Testing: $name");
    }


    /**
     * Tests statName() on unmodified zip file.
     * @dataProvider noExtrasZipFileProvider
     */
    public function testStatNameUnmodified(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'statName', $this->createNameAndFlagsParameterLists($name), 'assertStat');
    }

    /**
     * encryption_method field depends on ext-zip version (>= 1.14.0) and version of libzip (>= 1.2.0)
     */
    private function assertStat($expected, $actual, $message)
    {
        if (is_array($expected) && is_Array($actual) && !isset($expected['encryption_method'])) {
            unset($actual['encryption_method']);
        }

        $this->assertEquals($expected, $actual, $message);
    }
}
