<?php


namespace iqb;

class ZipArchiveStatTest extends ZipArchiveTestBase
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
        $methods = [
            'statIndex' => [
                $this->createFlagCombinations("FL_UNCHANGED"),
                \array_keys($files),
            ],
            'statName' => [
                $this->createFlagCombinations("FL_NOCASE", "FL_NODIR", "FL_UNCHANGED"),
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
     * @dataProvider providerForTestGetFrom
     */
    public function testStat(string $methodName, string $fileName, $qualifier, int $flags)
    {
        $this->runMethodTest($methodName, $fileName, [$qualifier, $flags], 'assertStat');
    }

    /**
     * encryption_method field depends on ext-zip version (>= 1.14.0) and version of libzip (>= 1.2.0)
     */
    protected function assertStat($expected, $actual, $message)
    {
        if (is_array($expected) && is_Array($actual) && !isset($expected['encryption_method'])) {
            unset($actual['encryption_method']);
        }

        $this->assertSame($expected, $actual, $message);
    }
}
