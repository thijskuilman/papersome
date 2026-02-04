<?php

namespace App\Providers\Filament;

use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\ActivityLogs\ActivityLogResource;
use App\Filament\Resources\Collections\CollectionResource;
use App\Filament\Resources\Sources\SourceResource;
use App\Models\Collection;
use App\Models\Source;
use App\Settings\ApplicationSettings;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use MWGuerra\Onboarding\Filament\OnboardingPlugin;
use MWGuerra\Onboarding\Filament\Widgets\OnboardingWidget;
use MWGuerra\Onboarding\OnboardingStep;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->registration()
            ->id('admin')
            ->path('')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->login()
            ->colors([
                'primary' => Color::Violet,
            ])
            ->brandLogo(fn (): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View => view('filament.admin.logo'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                OnboardingWidget::class,
            ])
            ->userMenuItems([
                Action::make('logs')
                    ->url(fn (): string => ActivityLogResource::getUrl())
                    ->icon(Heroicon::OutlinedDocumentText),

                Action::make('settings')
                    ->url(fn (): string => ManageSettings::getUrl())
                    ->icon(Heroicon::OutlinedCog),
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
                OnboardingPlugin::make()
                    ->configure(function ($onboarding): void {
                        $onboarding
                            ->welcomeTitle('Welcome to Papersome!')
                            ->welcomeDescription('Complete these steps to get started.')
                            ->steps([
                                OnboardingStep::make('create-first-source')
                                    ->title('Step 1: create your first source')
                                    ->description('The first step is to add an RSS feed to pull content from.')
                                    ->icon('heroicon-o-rss')
                                    ->iconColor('primary')
                                    ->linkToResourceCreate(SourceResource::class)
                                    ->completedWhen(fn (): bool => Source::count() > 0)
                                    ->buttonLabel('Create source')
                                    ->order(1),

                                OnboardingStep::make('create-collection')
                                    ->title('Step 2: create a collection')
                                    ->description('Bundle sources to read in a newspaper or magazine format.')
                                    ->icon('heroicon-o-newspaper')
                                    ->iconColor('warning')
                                    ->linkToResource(CollectionResource::class)
                                    ->completedWhen(fn (): bool => Collection::count() > 0)
                                    ->buttonLabel('Create collection')
                                    ->order(2),

                                OnboardingStep::make('set-up-delivery-channel')
                                    ->title('Step 3: automate delivery')
                                    ->description('Automate delivery to Booklore.')
                                    ->icon('heroicon-o-truck')
                                    ->iconColor('info')
                                    ->linkToPage(ManageSettings::class)
                                    ->completedWhen(fn (): bool => app(ApplicationSettings::class)->booklore_refresh_token !== null)
                                    ->buttonLabel('Open settings')
                                    ->order(3),
                            ]);
                    }),
            ]);
    }
}
