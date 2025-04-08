<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Navigation\MenuItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Pages\EditProfilePage; // Tambahkan ini untuk edit profil
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use Nuxtifyts\DashStackTheme\DashStackThemePlugin;
use ShuvroRoy\FilamentSpatieLaravelHealth\FilamentSpatieLaravelHealthPlugin;
use pxlrbt\FilamentEnvironmentIndicator\EnvironmentIndicatorPlugin;
use \Croustibat\FilamentJobsMonitor\FilamentJobsMonitorPlugin;
use Saade\FilamentLaravelLog\FilamentLaravelLogPlugin;



use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;


// use DiogoGPinto\AuthUIEnhancer\AuthUIEnhancerPlugin;
// use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;



class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
        
            ->default()
            ->id('admin')
            ->path('admin')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s') // Opsional: Atur polling time
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn () => Auth::user()->name)
                    ->url('#') // Membuat item tidak dapat diklik
                    ->icon('heroicon-o-user'), // Tambahkan icon opsional
                'edit_profile' => MenuItem::make()
                    ->label('Edit Profil')
                    ->url(fn (): string => EditProfilePage::getUrl())
                    ->icon('heroicon-o-user-circle'),
                'logout' => MenuItem::make()
                    ->label('Log out')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
            ])
            
            
            ->login()
            
            ->spa()
                       
            ->collapsedSidebarWidth('20rem')
            ->sidebarWidth('20rem')
            // ->brandLogo(asset('images/jelantik.jpeg'))
            // ->brandLogo(asset('images/jelantik.jpeg'))
            // ->brandLogoHeight('9rem')
            // ->brandName('Billing')

            ->brandLogo(fn () => view('filament.admin.logo'))
            
            
            // ->brandLogo(fn () => view('filament.admin.dashboard'))
            ->favicon(asset('images/jelantik.jpeg'))
           
            ->font('Poppins')
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'primary' => Color::Indigo,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->widgets([
                // \App\Filament\Widgets\LaporanPelangganStats::class,  // Widget stats yang sudah dibuat
                // \App\Filament\Widgets\JumlahPelangganPerAlamatChart::class,  // Widget chart yang sudah dibuat
                // \App\Filament\Widgets\JumlahPelangganJakinetChart::class,  // Widget
                // \App\Filament\Widgets\JumlahPelangganJelantikChart::class,  // Widget

                
            ])
            ->resources([
                config('filament-logger.activity_resource')
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \Cmsmaxinc\FilamentErrorPages\FilamentErrorPagesPlugin::make(),
                EasyFooterPlugin::make()
                ->withBorder()
                ->withLogo(
                    'https://ajnusa.com/images/artacom.png', // Path to logo
                    'https://ajnusa.com/'                                // URL for logo link (optional)
                )
                ->withLinks([
                    ['title' => 'Dev', 'url' => 'https://www.instagram.com/amad.dyk/'],
                ])
                ->withLoadTime('This page loaded in'),
                DashStackThemePlugin::make(),
                (FilamentSpatieLaravelHealthPlugin::make()),
                // EnvironmentIndicatorPlugin::make(),
                (\TomatoPHP\FilamentArtisan\FilamentArtisanPlugin::make()),
                // \TomatoPHP\FilamentSettingsHub\FilamentSettingsHubPlugin::make()
                // ->allowSiteSettings()
                // ->allowSocialMenuSettings(),
                // ->allowShield()
                FilamentApexChartsPlugin::make(),
                    
                    FilamentJobsMonitorPlugin::make()
                        ->enableNavigation(),
                        
                //FilamentLaravelLogPlugin::make(),

                
                 \FilipFonal\FilamentLogManager\FilamentLogManager::make(),
            

                ]);
            
    }

    
}