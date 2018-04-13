<?php

namespace morgue\zip;

/**
 * Convert dos date and time values to a \DateTimeInterface object.
 * DOS times are stored as local time values (not UTC), so use the supplied timezone (defaults to \date_default_timezone_get()).
 *
 * @param int $dosTime
 * @param int $dosDate
 * @param string $timezone
 * @return \DateTimeInterface
 * @throws \Exception
 */
function dos2DateTime(int $dosTime, int $dosDate, string $timezone = null) : \DateTimeInterface
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

    return new \DateTimeImmutable("$year-$month-$day $hours:$minutes:$seconds", new \DateTimeZone($timezone ? $timezone : \date_default_timezone_get()));
}


/**
 * Create dos time and date integers from datetime object.
 *
 * @param \DateTimeInterface $datetime
 * @return array
 */
 function dateTime2Dos(\DateTimeInterface $datetime) : array
{
    $year =(int)$datetime->format('Y');
    $month =(int)$datetime->format('n');
    $day =(int)$datetime->format('j');
    $hour =(int)$datetime->format('G');
    $minute =(int)$datetime->format('i');
    $second =(int)$datetime->format('s');

    if (($year === 1979) && ($month === 11) && ($day === 30)) {
        $day = 0;
        $month = 0;
        $year = 1980;
    }

    elseif (($year === 1979) && ($month === 12)) {
        $month = 0;
        $year = 1980;
    }

    elseif ($year < 1980) {
        throw new \InvalidArgumentException("DOS date and time can not represent dates before 1979-11-30");
    }

    $dosTime =
        ($second >> 1)
        + ($minute << 5)
        + ($hour << 11)
    ;

    $dosDate =
        $day
        + ($month << 5)
        + (($year - 1980) << 9)
    ;

    return [$dosTime, $dosDate, 'time' => $dosTime, 'date' => $dosDate];
}
