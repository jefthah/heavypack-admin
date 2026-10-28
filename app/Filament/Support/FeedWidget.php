<?php

namespace App\Filament\Support;

use Carbon\Exceptions\InvalidFormatException;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Shared helpers for the compact "latest activity" cards on the dashboard.
 */
abstract class FeedWidget extends Widget
{
    protected static bool $isLazy = false;

    /**
     * @return array{initial: string, tone: string}
     */
    protected function avatarFor(?string $seed): array
    {
        $seed = trim((string) $seed);
        $tones = ['orange', 'ink', 'sand', 'stone'];

        return [
            'initial' => $seed === '' ? '?' : Str::upper(Str::substr($seed, 0, 1)),
            'tone' => $tones[crc32(Str::lower($seed)) % count($tones)],
        ];
    }

    /**
     * @return array{relative: ?string, full: ?string, iso: ?string}
     */
    protected function dateFor(mixed $value): array
    {
        try {
            $date = blank($value) ? null : Carbon::parse($value);
        } catch (InvalidFormatException) {
            $date = null;
        }

        return [
            'relative' => $date?->diffForHumans(),
            'full' => $date?->translatedFormat('d M Y, H:i'),
            'iso' => $date?->toIso8601String(),
        ];
    }

    protected function formatTotal(int $total): string
    {
        return number_format($total, 0, ',', '.');
    }
}
