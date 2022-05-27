<?php

declare(strict_types=1);

namespace DiamondStrider1\QuickFriends\UserInterface;

final class TimeFormat
{
    public static function secondsToString(int $timeInSeconds): string
    {
        $days = floor($timeInSeconds / 86400);
        $hours = floor($timeInSeconds / 3600) - $days * 24;
        $minutes = floor($timeInSeconds / 60) - $hours * 60;
        $seconds = $timeInSeconds - $minutes * 60;

        $components = [];
        if ($days > 0) {
            $components[] = "$days d";
        }
        if ($hours > 0) {
            $components[] = "$hours hr";
        }
        if ($minutes > 0) {
            $components[] = "$minutes min";
        }
        if ($seconds > 0) {
            $components[] = "$seconds sec";
        }

        return implode(', ', $components);
    }
}
