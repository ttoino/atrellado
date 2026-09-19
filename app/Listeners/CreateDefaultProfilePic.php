<?php

namespace App\Listeners;

use App\Events\UserCreated;
use App\Events\UserUpdated;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateDefaultProfilePic
{
    const TARGET_CONTRAST = 4.5;

    private static function srgbToSpaceless(float $component)
    {
        return $component <= 0.03928 ? $component / 12.92 : (($component + 0.055) / 1.055) ** 2.4;
    }

    private static function hsvToSRGB(float $h, float $s, float $v)
    {
        $sector = $h / 60;
        $c = $s * $v;
        $x = $c * (1 - abs($sector % 2 - 1));

        if ($sector < 1) {
            return [$c, $x, 0];
        }
        if ($sector < 2) {
            return [$x, $c, 0];
        }
        if ($sector < 3) {
            return [0, $c, $x];
        }
        if ($sector < 4) {
            return [0, $x, $c];
        }
        if ($sector < 5) {
            return [$x, 0, $c];
        }

        return [$c, 0, $x];

    }

    // Based on the formulas at
    // https://www.w3.org/TR/WCAG20/#relativeluminancedef and
    // https://www.w3.org/TR/WCAG20/#contrast-ratiodef
    private static function generateColor(int $seed)
    {
        mt_srand($seed);

        $RAND_MAX = mt_getrandmax();

        $h = ((float) mt_rand()) / $RAND_MAX * 360;

        $contrast = -1;
        do {
            $s = ((float) mt_rand()) / $RAND_MAX * .4 + .6;
            $v = ((float) mt_rand()) / $RAND_MAX * .4 + .6;

            [$r_srgb, $g_srgb, $b_srgb] = self::hsvToSRGB($h, $s, $v);

            $r = self::srgbToSpaceless($r_srgb);
            $g = self::srgbToSpaceless($g_srgb);
            $b = self::srgbToSpaceless($b_srgb);

            $y = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;

            $contrast = 1.05 / ($y + 0.05);
        } while ($contrast < static::TARGET_CONTRAST);

        Log::debug('COLOR: ', [$h, $s, $v, $r_srgb, $g_srgb, $b_srgb]);

        return sprintf('#%02x%02x%02x', $r_srgb * 255, $g_srgb * 255, $b_srgb * 255);
    }

    public function handle(UserCreated|UserUpdated $event)
    {
        Storage::put(
            "public/users/default_{$event->user->id}.svg",
            view('other.pfp', [
                'background' => self::generateColor($event->user->id),
                'text' => $event->user->name[0],
            ])->render());
    }
}
