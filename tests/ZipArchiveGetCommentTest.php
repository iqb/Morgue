<?php


namespace iqb;

class ZipArchiveGetCommentTest extends ZipArchiveTestBase
{
    /**
     * Data provider for testGetFrom()
     * @return array
     */
    public function providerForTestGetFrom()
    {
        $fileName = self::ZIP_COMMENTS;
        $basename = \basename($fileName);
        $files = $this->getArchiveEntries($fileName, true);
        $methods = [
            'getCommentIndex' => [
                $this->createFlagCombinations("FL_UNCHANGED"),
                \array_keys($files),
            ],
            'getCommentName' => [
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
    public function testGetFrom(string $methodName, string $fileName, $qualifier, int $flags)
    {
        $this->runMethodTest($methodName, $fileName, [$qualifier, $flags]);
    }

    public function testInvalidIndex()
    {
        $this->runMethodTest('getCommentIndex', self::ZIP_COMMENTS, [49]);
    }

    public function testInvalidName()
    {
        $this->runMethodTest('getCommentName', self::ZIP_COMMENTS, ['this/file/really/does/not/exists.txt']);
    }
}
