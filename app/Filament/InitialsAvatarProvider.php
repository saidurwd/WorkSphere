<?php

namespace App\Filament;

use Filament\AvatarProviders\Contracts\AvatarProvider;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * A person's initials on a coloured disc, drawn locally as an inline SVG.
 *
 * Filament's default fetches the avatar from a third-party service, which would
 * send people's names off-site and is blocked by this application's content
 * security policy anyway.
 */
class InitialsAvatarProvider implements AvatarProvider
{
    public function get(Model|Authenticatable $record): string
    {
        $initials = collect(preg_split('/\s+/', trim((string) Filament::getNameForDefaultAvatar($record))) ?: [])
            ->map(fn (string $part): string => mb_strtoupper(mb_substr(preg_replace('/^[^\p{L}\p{N}]+/u', '', $part) ?? '', 0, 1)))
            ->filter()
            ->take(2)
            ->implode('');

        $initials = htmlspecialchars($initials === '' ? '?' : $initials, ENT_QUOTES);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" fill="#4338ca"/>'
            .'<text x="50%" y="50%" dy=".35em" text-anchor="middle" fill="#ffffff" font-family="sans-serif" font-size="26">'.$initials.'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
