<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Bengali rendering of numbers, dates and times.
 *
 * The bill document and the dashboard both need these, so the digit map and
 * the month names live here rather than being repeated in each Blade file.
 */
class Bn
{
    /** Western digit => Bengali numeral. */
    public const DIGITS = [
        '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
        '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
    ];

    /** Gregorian month names in Bengali, keyed by month number (1-12). */
    public const MONTHS = [
        1 => 'জানুয়ারি', 2 => 'ফেব্রুয়ারি', 3 => 'মার্চ', 4 => 'এপ্রিল',
        5 => 'মে', 6 => 'জুন', 7 => 'জুলাই', 8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর', 10 => 'অক্টোবর', 11 => 'নভেম্বর', 12 => 'ডিসেম্বর',
    ];

    /** Weekday names in Bengali, keyed as Carbon's dayOfWeek (0 = Sunday). */
    public const WEEKDAYS = [
        0 => 'রবিবার', 1 => 'সোমবার', 2 => 'মঙ্গলবার', 3 => 'বুধবার',
        4 => 'বৃহস্পতিবার', 5 => 'শুক্রবার', 6 => 'শনিবার',
    ];

    /**
     * Bengali names the parts of the day more finely than English does, so the
     * greeting follows those bands rather than a morning/afternoon/evening split.
     * [start hour (inclusive), end hour (inclusive), greeting, day part].
     */
    public const DAY_PARTS = [
        [4, 5, 'শুভ ভোর', 'ভোর'],
        [6, 11, 'শুভ সকাল', 'সকাল'],
        [12, 15, 'শুভ দুপুর', 'দুপুর'],
        [16, 17, 'শুভ বিকাল', 'বিকাল'],
        [18, 19, 'শুভ সন্ধ্যা', 'সন্ধ্যা'],
        // 20:00 through 03:59 — the band that wraps past midnight.
        [20, 3, 'শুভ রাত্রি', 'রাত'],
    ];

    /**
     * Convert every western digit in the value to its Bengali numeral.
     */
    public static function digits(string|int|float|null $value): string
    {
        return strtr((string) $value, self::DIGITS);
    }

    /**
     * "মাস - বছর", e.g. সেপ্টেম্বর - ২০২৬.
     */
    public static function monthYear(?Carbon $date): string
    {
        return $date
            ? self::MONTHS[$date->month].' - '.self::digits($date->format('Y'))
            : '—';
    }

    /**
     * "দিন-মাস-বছর", e.g. ০৫-সেপ্টেম্বর-২০২৬.
     */
    public static function date(?Carbon $date): string
    {
        return $date
            ? self::digits($date->format('d')).'-'.self::MONTHS[$date->month].'-'.self::digits($date->format('Y'))
            : '—';
    }

    /**
     * "বার, দিন মাস বছর", e.g. শুক্রবার, ০৫ সেপ্টেম্বর ২০২৬.
     */
    public static function fullDate(?Carbon $date): string
    {
        if (! $date) {
            return '—';
        }

        return self::WEEKDAYS[$date->dayOfWeek].', '
            .self::digits($date->format('d')).' '
            .self::MONTHS[$date->month].' '
            .self::digits($date->format('Y'));
    }

    /**
     * The day part a given hour falls in: [greeting, label].
     */
    public static function dayPart(int $hour): array
    {
        foreach (self::DAY_PARTS as [$from, $to, $greeting, $label]) {
            // The night band runs past midnight, so it matches either side of it.
            $matches = $from <= $to
                ? ($hour >= $from && $hour <= $to)
                : ($hour >= $from || $hour <= $to);

            if ($matches) {
                return [$greeting, $label];
            }
        }

        return ['শুভ দিন', 'দিন'];
    }

    /**
     * The greeting for the time of day, e.g. শুভ দুপুর.
     */
    public static function greeting(?Carbon $at = null): string
    {
        return self::dayPart(($at ?? Carbon::now())->hour)[0];
    }

    /**
     * "দুপুর ০৩:৪২:০৭" — the day part reads as the meridiem, which is how the
     * time is spoken in Bangla, so no separate AM/PM is needed.
     */
    public static function time(?Carbon $at = null, bool $withSeconds = true): string
    {
        $at ??= Carbon::now();
        $label = self::dayPart($at->hour)[1];

        return  $label.' '.self::digits($at->format($withSeconds ? 'h:i:s' : 'h:i'));
    }
}
