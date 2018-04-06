<?php

namespace iqb;

use PHPUnit\Framework\TestCase;

class ZipArchiveTest extends TestCase
{
    private $zipFileNoExtras = __DIR__ . '/test-no-extras.zip';
    private $zipFileComments = __DIR__ . '/comments.zip';
    private $zipFileNoComment = __DIR__ . '/nocomment.zip';


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
     * @dataProvider noExtrasZipFileProvider
     */
    public function testGetStream(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'getStream', [[$fileName]], 'assertSame');
    }


    /**
     * Tests statName() on unmodified zip file.
     * @dataProvider noExtrasZipFileProvider
     */
    public function testLocateNameUnmodified(string $fileName, int $index, string $name)
    {
        $this->compareMethodResults($fileName, 'locateName', $this->createNameAndFlagsParameterLists($name), 'assertSame');
    }
}
