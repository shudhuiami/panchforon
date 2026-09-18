<?php

namespace App\Providers\Filament;

use App\Filament\AvatarProviders\InitialsAvatarProvider;
use App\Filament\Studio\Widgets\StudioOverview;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The Creator Studio: where someone who writes recipes for the site works.
 *
 * It is a panel of its own rather than a corner of the admin one, because the
 * two answer different questions. The admin panel is about the catalogue as a
 * whole — every recipe, every account, the moderation queue. This is about one
 * person's own work, and nothing here should ever show them anyone else's.
 *
 * Which is why the discovery roots below point at their own directory tree.
 * Aiming them at app/Filament/Resources would register the user, flag, page,
 * unit and ingredient screens under /studio too; their policies would refuse
 * each one, so nothing would leak, but the panel would carry eight route
 * groups that exist only to say no.
 */
class StudioPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            /**
             * Deliberately not ->default(): the admin panel holds that, and
             * code outside a panel request — a resource URL built from a
             * notification, say — must keep resolving there.
             */
            ->id('studio')
            ->path('studio')
            /**
             * The admin panel's theme, reused rather than forked. Its @source
             * globs already cover app/Filament/**, so a studio class is picked
             * up with no CSS change — which keeps this whole panel a pure-PHP
             * change, with no fourth Vite input and no rebuilt bundle to ship.
             */
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->brandName('Panchforon Studio')
            ->defaultAvatarProvider(InitialsAvatarProvider::class)
            ->colors([
                'primary' => Color::hex('#FC7100'),
                'gray' => Color::Stone,
                'danger' => Color::hex('#F45F67'),
                'success' => Color::hex('#5CA135'),
                'warning' => Color::hex('#FB8818'),
            ])
            ->font('Instrument Sans Variable', provider: LocalFontProvider::class)
            ->serifFont('Fraunces Variable', provider: LocalFontProvider::class)
            ->maxContentWidth(Width::ScreenTwoExtraLarge)
            ->sidebarCollapsibleOnDesktop()
            ->discoverResources(in: app_path('Filament/Studio/Resources'), for: 'App\Filament\Studio\Resources')
            ->discoverPages(in: app_path('Filament/Studio/Pages'), for: 'App\Filament\Studio\Pages')
            ->pages([
                Dashboard::class,
            ])
            /**
             * One widget, and a studio-specific one. None of the admin panel's
             * widgets are reused: their figures are site-wide — total users,
             * the size of the moderation queue — and none of that is a
             * creator's business. This one counts only their own work.
             */
            ->widgets([
                StudioOverview::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
