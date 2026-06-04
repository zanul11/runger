<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->brandName('Runger')
            ->brandLogo(asset('logo-white.png'))
            ->brandLogoHeight('1.9rem')
            ->favicon(asset('assets/runger-logo.png'))
            ->colors([
                'primary' => Color::hex('#1B3FAE'),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString($this->loginStyles()),
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn (): HtmlString => new HtmlString(
                    '<p class="runger-tagline">Lari Bareng, Sehat Bareng</p>'
                ),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
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

    /**
     * Branded styling for the auth (login) screen.
     */
    protected function loginStyles(): string
    {
        $logo = asset('logo-white.png');

        return <<<HTML
<style>
    /* ===== Runger branded login ===== */
    .fi-simple-layout{
        position:relative;min-height:100vh;
        background:
            radial-gradient(900px 520px at 85% -12%, rgba(226,240,84,.16), transparent 60%),
            radial-gradient(760px 480px at 5% 112%, rgba(27,63,174,.50), transparent 62%),
            linear-gradient(180deg, #0F2680 0%, #0A0F2C 72%);
    }
    /* subtle grid, faded toward edges */
    .fi-simple-layout::before{
        content:'';position:fixed;inset:0;pointer-events:none;z-index:0;
        background-image:
            linear-gradient(transparent 96%, rgba(255,255,255,.05) 96%),
            linear-gradient(90deg, transparent 96%, rgba(255,255,255,.05) 96%);
        background-size:46px 46px;
        -webkit-mask:radial-gradient(circle at 50% 38%, #000 24%, transparent 72%);
                mask:radial-gradient(circle at 50% 38%, #000 24%, transparent 72%);
    }
    /* the Runger logo as a giant faded background watermark */
    .fi-simple-layout::after{
        content:'';position:fixed;left:50%;top:46%;transform:translate(-50%,-50%);
        width:min(960px, 150vw);height:min(960px, 150vw);
        background:url('{$logo}') no-repeat center center;
        background-size:contain;
        opacity:.08;mix-blend-mode:screen;
        pointer-events:none;z-index:0;
    }
    .fi-simple-main-ctn{position:relative;z-index:1}

    /* the login card */
    .fi-simple-main{
        border:1px solid rgba(255,255,255,.14) !important;
        border-radius:20px !important;
        box-shadow:0 30px 90px rgba(0,0,0,.5) !important;
        overflow:hidden;
    }
    /* volt accent stripe on top of the card */
    .fi-simple-main::before{
        content:'';position:absolute;top:0;left:0;right:0;height:4px;
        background:linear-gradient(90deg, #1B3FAE, #E2F054);
    }

    /* white logo on a brand-blue badge so it stays visible on light surfaces */
    .fi-logo{
        width:auto;box-sizing:content-box;
        padding:9px 15px;background:#1B3FAE;
        border-radius:12px;
        box-shadow:0 12px 34px rgba(0,0,0,.4);
    }

    /* tagline under the heading */
    .runger-tagline{
        text-align:center;margin:-0.35rem 0 1.25rem;
        font-size:.72rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;
        color:#1B3FAE;
    }
    .dark .runger-tagline{color:#E2F054}

    /* tidy up the sign-in button */
    .fi-simple-main .fi-btn{font-weight:600;letter-spacing:.02em}

    @media (prefers-reduced-motion:reduce){
        .fi-simple-layout::before{display:none}
    }
</style>
HTML;
    }
}
