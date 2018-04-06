<?php


namespace iqb;

class ZipArchiveExternalAttributesTest extends ZipArchiveTestBase
{
    /**
     * Data provider for testGetExternalAttributes()
     * @return array
     */
    public function providerForTestGetExternalAttributes()
    {
        $fileName = self::ZIP_NO_EXTRAS;
        $basename = \basename($fileName);
        $files = $this->getArchiveEntries($fileName, true);
        $methods = [
            'getExternalAttributesIndex' => [
                $this->createFlagCombinations("FL_UNCHANGED"),
                \array_keys($files),
            ],
            'getExternalAttributesName' => [
                $this->createFlagCombinations("FL_NOCASE", "FL_NODIR","FL_UNCHANGED"),
                \array_merge(...$files),
            ],
        ];

        $data = [];

        foreach ($methods as $method => list($flags, $filelist)) {
            foreach ($filelist as $qualifier) {
                foreach ($flags as $flagName => $flagValue) {
                    $data["$method(), $basename, $qualifier, $flagName"] = [$method, $fileName, $qualifier, $flagValue];
                }
            }
        }

        return $data;
    }

    /**
     * @dataProvider providerForTestGetExternalAttributes
     */
    public function testGetExternalAttributes(string $methodName, string $fileName, $qualifier, int $flags)
    {
        $zipExt = new \ZipArchive();
        $zipExt->open($fileName);
        $zipPkg = new ZipArchive();
        $zipPkg->open($fileName);

        $opsysExt = $opsysPkg = $attrExt = $attrPkg = null;
        $this->assertSame($zipExt->$methodName($qualifier, $opsysExt, $attrExt, $flags), $zipPkg->$methodName($qualifier, $opsysPkg, $attrPkg, $flags), "method call");
        $this->assertSame($opsysExt, $opsysPkg, "opsys check");
        $this->assertSame($attrExt, $attrPkg, "attr check");
        $this->assertZipArchiveStatus($zipExt, $zipPkg);
    }


}