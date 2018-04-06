<?php


namespace iqb;

use PHPUnit\Framework\TestCase;

/**
 * Contains some helper methods for ZipArchive tests
 */
abstract class ZipArchiveTestBase extends TestCase
{
    const ZIP_NO_EXTRAS = __DIR__ . '/test-no-extras.zip';
    const ZIP_COMMENTS = __DIR__ . '/comments.zip';

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
