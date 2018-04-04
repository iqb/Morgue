<?php

namespace iqb;

use PHPUnit\Framework\TestCase;

class ZipArchiveTest extends TestCase
{
    private $zipFileNoExtras = 'test-no-extras.zip';


    public function setUp()
    {
        parent::setUp();

        // DOS date+time is stored as a local timezone variable, not UTC
        // libzip uses TZ environment variable to get the timezone whereas this package uses the PHP internal date timezone
        // Set both to UTC to avoid test problems system timezone settings
        \date_default_timezone_set('UTC');
        \putenv("TZ=UTC");
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


    public function noExtrasIndexProvider()
    {
        $return = [];

        $fromExt = new \ZipArchive();
        $fromExt->open($this->zipFileNoExtras);

        for ($i=0; $i<$fromExt->numFiles; $i++) {
            $return[]  = [$i, $fromExt->statIndex($i)['name']];
        }

        return $return;
    }


    /**
     * Tests statName() on unmodified zip file.
     * @dataProvider noExtrasIndexProvider
     */
    public function testLocateNameUnmodified(int $index, string $name)
    {
        $fromExt = new \ZipArchive();
        $fromExt->open($this->zipFileNoExtras);

        $fromPkg = new ZipArchive();
        $fromPkg->open($this->zipFileNoExtras);

        $this->assertSame($fromExt->locateName($name), $fromPkg->locateName($name));

        $this->assertSame($fromExt->locateName($name, \ZipArchive::FL_NOCASE), $fromPkg->locateName($name, ZipArchive::FL_NOCASE));
        $this->assertSame($fromExt->locateName(\strtolower($name), \ZipArchive::FL_NOCASE), $fromPkg->locateName(\strtolower($name), ZipArchive::FL_NOCASE));
        $this->assertSame($fromExt->locateName(\strtoupper($name), \ZipArchive::FL_NOCASE), $fromPkg->locateName(\strtoupper($name), ZipArchive::FL_NOCASE));

        $basename = \basename($name);
        $this->assertSame($fromExt->locateName($name, \ZipArchive::FL_NODIR), $fromPkg->locateName($name, ZipArchive::FL_NODIR), 'Getting: ' . $name);
        $this->assertSame($fromExt->locateName($basename, \ZipArchive::FL_NODIR), $fromPkg->locateName($basename, ZipArchive::FL_NODIR), 'Getting: ' . $basename);

        $this->assertSame($fromExt->locateName($name, \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->locateName($name, ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
        $this->assertSame($fromExt->locateName(\strtolower($name), \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->locateName(\strtolower($name), ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
        $this->assertSame($fromExt->locateName(\strtoupper($name), \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->locateName(\strtoupper($name), ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
        $this->assertSame($fromExt->locateName($basename, \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->locateName($basename, ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
        $this->assertSame($fromExt->locateName(\strtolower($basename), \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->locateName(\strtolower($basename), ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
        $this->assertSame($fromExt->locateName(\strtoupper($basename), \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->locateName(\strtoupper($basename), ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
    }


    /**
     * Tests statIndex() on unmodified zip file.
     * @dataProvider noExtrasIndexProvider
     */
    public function testStatUnmodified(int $index, string $name)
    {
        $fromExt = new \ZipArchive();
        $fromExt->open($this->zipFileNoExtras);

        $fromPkg = new ZipArchive();
        $fromPkg->open($this->zipFileNoExtras);

        $this->assertEquals($fromExt->statIndex($index), $fromPkg->statIndex($index), "Testing: $name");
    }


    /**
     * Tests statName() on unmodified zip file.
     * @dataProvider noExtrasIndexProvider
     */
    public function testStatNameUnmodified(int $index, string $name)
    {
        $fromExt = new \ZipArchive();
        $fromExt->open($this->zipFileNoExtras);

        $fromPkg = new ZipArchive();
        $fromPkg->open($this->zipFileNoExtras);

        $this->assertEquals($fromExt->statName($name), $fromPkg->statName($name));

        $this->assertEquals($fromExt->statName($name, \ZipArchive::FL_NOCASE), $fromPkg->statName($name, ZipArchive::FL_NOCASE));
        $this->assertEquals($fromExt->statName(\strtolower($name), \ZipArchive::FL_NOCASE), $fromPkg->statName(\strtolower($name), ZipArchive::FL_NOCASE));
        $this->assertEquals($fromExt->statName(\strtoupper($name), \ZipArchive::FL_NOCASE), $fromPkg->statName(\strtoupper($name), ZipArchive::FL_NOCASE));

        $basename = \basename($name);
        $this->assertEquals($fromExt->statName($name, \ZipArchive::FL_NODIR), $fromPkg->statName($name, ZipArchive::FL_NODIR));
        $this->assertEquals($fromExt->statName($basename, \ZipArchive::FL_NODIR), $fromPkg->statName($basename, ZipArchive::FL_NODIR));

        $this->assertEquals($fromExt->statName($name, \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->statName($name, ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
        $this->assertEquals($fromExt->statName(\strtolower($name), \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->statName(\strtolower($name), ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
        $this->assertEquals($fromExt->statName(\strtoupper($name), \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->statName(\strtoupper($name), ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
        $this->assertEquals($fromExt->statName($basename, \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->statName($basename, ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
        $this->assertEquals($fromExt->statName(\strtolower($basename), \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->statName(\strtolower($basename), ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
        $this->assertEquals($fromExt->statName(\strtoupper($basename), \ZipArchive::FL_NODIR|\ZipArchive::FL_NOCASE), $fromPkg->statName(\strtoupper($basename), ZipArchive::FL_NODIR|ZipArchive::FL_NOCASE));
    }
}
