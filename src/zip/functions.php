<?php

namespace iqb\zip;

/**
 * Convert dos date and time values to a \DateTimeInterface object
 *
 * @param int $dosTime
 * @param int $dosDate
 * @return \DateTimeInterface
 * @throws \Exception
 */
function dos2DateTime(int $dosTime, int $dosDate) : \DateTimeInterface
{
    // Bits 0-4: seconds / 2
    $seconds = (($dosTime & 0x1F) << 1);
    // Bits 5-10: minutes
    $minutes = (($dosTime >> 5) & 0x3F);
    // Bits 11-15: hours
    $hours = (($dosTime >> 11) & 0x1F);

    // Bits 0-4: day
    $day = ($dosDate & 0x1F);
    // Bits 5-8: month
    $month = (($dosDate >> 5) & 0x0F);
    // Bits 9-15: year - 1980
    $year = (($dosDate >> 9) & 0x7F) + 1980;

    return new \DateTimeImmutable("$year-$month-$day $hours:$minutes:$seconds", new \DateTimeZone('UTC'));
}

/**
 * Create dos time and date integers from datetime object.
 *
 * @param \DateTimeInterface $datetime
 * @return array
 */
 function dateTime2Dos(\DateTimeInterface $datetime) : array
{
    /* @var $datetime \DateTimeImmutable */
    $date = \getdate($datetime->setTimezone(new \DateTimeZone('UTC'))->getTimestamp());

    if (($date['year'] === 1979) && ($date['mon'] === 11) && ($date['mday'] === 30)) {
        $date['mday'] = 0;
        $date['mon'] = 0;
        $date['year'] = 1980;
    }

    elseif (($date['year'] === 1979) && ($date['mon'] === 12)) {
        $date['mon'] = 0;
        $date['year'] = 1980;
    }

    elseif ($date['year'] < 1980) {
        throw new \InvalidArgumentException("DOS date and time can not represent dates before 1979-11-30");
    }

    $dosTime =
        ($date['seconds'] >> 1)
        + ($date['minutes'] << 5)
        + ($date['hours'] << 11)
    ;

    $dosDate =
        $date['mday']
        + ($date['mon'] << 5)
        + (($date['year'] - 1980) << 9)
    ;

    return [$dosTime, $dosDate, 'time' => $dosTime, 'date' => $dosDate];
}
