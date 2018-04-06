<?php


namespace iqb;

class ZipArchiveGetFromTest extends ZipArchiveTestBase
{
    /**
     * Data provider for testGetFrom()
     * @return array
     */
    public function providerForTestGetFrom()
    {
        $fileName = self::ZIP_NO_EXTRAS;
        $basename = \basename($fileName);
        $files = $this->getArchiveEntries($fileName, true);
        $offsets = [null, 0, 64, 600, 100000];
        $methods = [
            'getFromIndex' => [
                $this->createFlagCombinations("FL_COMPRESSED", "FL_UNCHANGED"),
                \array_keys($files),
            ],
            'getFromName' => [
                $this->createFlagCombinations("FL_COMPRESSED", "FL_NOCASE", "FL_NODIR", "FL_UNCHANGED"),
                \array_merge(...$files),
            ],
        ];

        $data = [];

        foreach ($methods as $method => list($flags, $filelist)) {
            foreach ($filelist as $qualifier) {
                foreach ($offsets as $offset) {
                    foreach ($flags as $flagName => $flagValue) {
                        $data["$method(), $basename, $qualifier, ".(\is_null($offset) ? 'null' : $offset).", $flagName"] = [$method, $fileName, $qualifier, $offset, $flagValue];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @dataProvider providerForTestGetFrom
     */
    public function testGetFrom(string $methodName, string $fileName, $qualifier, $offset, int $flags)
    {
        $zipExt = new \ZipArchive();
        $zipExt->open($fileName);
        $zipPkg = new ZipArchive();
        $zipPkg->open($fileName);

        $this->assertSame($zipExt->$methodName($qualifier, $offset, $flags), $zipPkg->$methodName($qualifier, $offset, $flags), "method call");
        $this->assertZipArchiveStatus($zipExt, $zipPkg);
    }
}
