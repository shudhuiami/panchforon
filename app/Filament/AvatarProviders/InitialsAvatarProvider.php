<?php

namespace App\Filament\AvatarProviders;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Renders initials as an inline SVG data URI.
 *
 * Filament's default provider points the avatar at ui-avatars.com, which means
 * an external request on every panel page load and the admin's name leaving
 * the server. This keeps it local: no third-party call, nothing to fail when
 * the host has no outbound access, and one less round trip on shared hosting.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = str(Filament::getNameForDefaultAvatar($record))
            ->trim()
            ->explode(' ')
            ->map(fn (string $segment): string => mb_substr(
                (string) preg_replace('/^[^\p{L}\p{N}]+/u', '', $segment), 0, 1
            ))
            ->filter()
            ->take(2)
            ->join('');

        $background = Color::convertToHex(FilamentColor::getColor('gray')[950] ?? Color::Gray[950]);

        $svg = <<<SVG
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
            <rect width="100" height="100" fill="{$background}"/>
            <text x="50" y="50" dy="0.35em" fill="#FFFFFF" font-size="42"
                  font-family="ui-sans-serif, system-ui, sans-serif" font-weight="500"
                  text-anchor="middle">{$this->escape($initials)}</text>
            </svg>
            SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
