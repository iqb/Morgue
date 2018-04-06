<?php


namespace iqb;

use PHPUnit\Framework\TestCase;

/**
 * Contains some helper methods for ZipArchive tests
 */
abstract class ZipArchiveTestBase extends TestCase
{
    /**
     * A test file without extra fields set (so no UNIX timestamp, etc.)
     */
    const ZIP_NO_EXTRAS = __DIR__ . '/archives/no-extra-fields.zip';

    /**
     * A test file containing an archive comment and some file comments
     */
    const ZIP_COMMENTS = __DIR__ . '/archives/archive-and-file-comments.zip';

    /**
     * A test file where all comments are empty
     */
    const ZIP_NO_COMMENTS = __DIR__ . '/archives/archive-and-file-comments-empty.zip';

    /**
     * A test file containing a large file (~16GB) of zeros to force ZIP64 structure, compressed with BZip2
     */
    const ZIP_ZIP64 = __DIR__ . '/archives/zip64-bzip2.zip';

    /**
     * A test file containing a file with STORE compression method
     */
    const ZIP_STORE = __DIR__ . '/archives/method-store.zip';

    /**
     * A test file containing a file with deflate compression method
     */
    const ZIP_DEFLATE = __DIR__ . '/archives/method-deflate.zip';

    /**
     * A test file containing a file with BZip2 compression method
     */
    const ZIP_BZIP2 = __DIR__ . '/archives/method-bzip2.zip';

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
     * Get all file names with their index from an archive
     *
     * @param string $fileName
     * @param bool $createNameVariants
     * @return array
     */
    final protected function getArchiveEntries(string $fileName, bool $createNameVariants = false)
    {
        $fromExt = new \ZipArchive();
        $fromExt->open($fileName);

        $files = [];
        for ($index=0; $index<$fromExt->numFiles; $index++) {
            if ($createNameVariants) {
                $name = $fromExt->statIndex($index)['name'];
                $basename = \basename($name);
                $files[$index] = [$name, \strtolower($name), \strtoupper($name), $basename, \strtolower($basename), \strtoupper($basename)];
            }

            else {
                $files[$index] = $fromExt->statIndex($index)['name'];
            }
        }
        return $files;
    }

    /**
     * From the supplied flag names return all possible combinations
     *
     * @param string ...$flagNames
     * @return array
     */
    final protected function createFlagCombinations(string ...$flagNames)
    {
        $flags = ["[unset]" => 0];
        for ($i=0; $i<\count($flagNames); $i++) {
            $name = '';
            $value = 0;

            for ($j=$i; $j<\count($flagNames); $j++) {
                $name .= (\strlen($name) ? '|' : '') . $flagNames[$j];
                $value |= \constant(ZipArchive::class . '::' . $flagNames[$j]);
                $flags[$name] = $value;
            }
        }
        return $flags;
    }

    final protected function runMethodTest(string $methodName, string $fileName, array $parameters, string $assertMethod = 'assertSame')
    {
        $zipExt = new \ZipArchive();
        $zipExt->open($fileName);
        $zipPkg = new ZipArchive();
        $zipPkg->open($fileName);

        $this->$assertMethod($zipExt->$methodName(...$parameters), $zipPkg->$methodName(...$parameters), "method call");
        $this->assertZipArchiveStatus($zipExt, $zipPkg);
    }

    final protected function assertZipArchiveStatus(\ZipArchive $zipExt, ZipArchive $zipPkg)
    {
        $this->assertSame($zipExt->status, $zipPkg->status, '$status');
        $this->assertSame($zipExt->statusSys, $zipPkg->statusSys, '$statusSys');
        $this->assertSame($zipExt->numFiles, $zipPkg->numFiles, '$numFiles');
        $this->assertSame($zipExt->comment, $zipPkg->comment, '$comment');
        $this->assertSame($zipExt->filename, $zipPkg->filename, '$filename');
        $this->assertSame($zipExt->getStatusString(), $zipPkg->getStatusString(), 'getStatusString()');
    }
}
