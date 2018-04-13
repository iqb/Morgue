<?php

namespace morgue\zip;

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    private $cases = [
        [0, 0, "1979-11-30 00:00:00", "1979-11-30 00:00:01"],
        [1, 0, "1979-11-30 00:00:02", "1979-11-30 00:00:03"],
        [0, 33, "1980-01-01 00:00:00"],
        [43507, 19583, "2018-03-31 21:15:38"],
    ];


    /**
     * @throws \Exception
     */
    public function testDos2DateTime()
    {
        foreach ($this->cases as list($dosTime, $dosDate, $datetime)) {
            $this->assertEquals(new \DateTimeImmutable($datetime), dos2DateTime($dosTime, $dosDate));
        }
    }

    /**
     * @throws \Exception
     */
    public function testDateTime2Dos()
    {
        foreach ($this->cases as $caseIndex => list($dosTime, $dosDate)) {
            for ($i=2; $i<\count($this->cases[$caseIndex]); $i++) {
                $datetime = $this->cases[$caseIndex][$i];
                $this->assertEquals(DateTime2dos(new \DateTimeImmutable($datetime)), [
                    $dosTime,
                    $dosDate,
                    'time' => $dosTime,
                    'date' => $dosDate,
                ], $datetime);
            }
        }
    }
}
