<?php

namespace App\Filament\Pages;

use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Liberu\Foundation\Organizations\Models\Team;

final class AccountSetupWizard extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.account-setup-wizard';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static string|\UnitEnum|null $navigationGroup = 'Get started';

    protected static ?int $navigationSort = -1;

    protected static ?string $navigationLabel = 'Setup guide';

    protected static ?string $title = 'Set up your workspace';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $team = $this->team();
        $settings = $team?->settings ?? [];

        $this->form->fill([
            'team_name' => $team?->name,
            'timezone' => $settings['timezone'] ?? 'UTC',
            'workflow' => $settings['workflow'] ?? 'maintenance',
            'oauth_provider' => $settings['oauth_provider'] ?? null,
            'oauth_client_id' => $settings['oauth_client_id'] ?? null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Wizard::make([
                    Step::make('Workspace')
                        ->description('Give your team a clear home')
                        ->icon('heroicon-o-building-office-2')
                        ->schema([
                            TextInput::make('team_name')
                                ->label('Workspace name')
                                ->helperText('This is shown in the team switcher and across your workspace.')
                                ->required()
                                ->maxLength(255),
                            Select::make('timezone')
                                ->label('Timezone')
                                ->options([
                                    'UTC' => 'UTC',
                                    'Europe/London' => 'Europe/London',
                                    'Europe/Paris' => 'Europe/Paris',
                                    'America/New_York' => 'America/New York',
                                    'America/Los_Angeles' => 'America/Los Angeles',
                                    'Asia/Singapore' => 'Asia/Singapore',
                                ])
                                ->searchable()
                                ->required(),
                            Select::make('workflow')
                                ->label('Primary workflow')
                                ->options([
                                    'maintenance' => 'Maintenance operations',
                                    'service' => 'Service delivery',
                                    'facilities' => 'Facilities management',
                                ])
                                ->required(),
                        ]),
                    Step::make('Connections')
                        ->description('Connect the services your team uses')
                        ->icon('heroicon-o-link')
                        ->schema([
                            Select::make('oauth_provider')
                                ->label('OAuth provider')
                                ->placeholder('Choose later')
                                ->options([
                                    'google' => 'Google Workspace',
                                    'github' => 'GitHub',
                                    'gitlab' => 'GitLab',
                                    'microsoft' => 'Microsoft',
                                    'slack' => 'Slack',
                                ])
                                ->helperText('Optional. You can also connect an account from your profile after setup.'),
                            TextInput::make('oauth_client_id')
                                ->label('OAuth client ID')
                                ->helperText('Only needed when this provider is enabled in your deployment.'),
                            TextInput::make('oauth_client_secret')
                                ->label('OAuth client secret')
                                ->password()
                                ->revealable()
                                ->helperText('Stored encrypted with the workspace settings.'),
                            TextInput::make('api_key')
                                ->label('Service API key')
                                ->password()
                                ->revealable()
                                ->helperText('Optional. Add a key only if an enabled integration requires one.'),
                        ]),
                    Step::make('Review')
                        ->description('You are ready to get to work')
                        ->icon('heroicon-o-check-circle')
                        ->schema([]),
                ])
                    ->persistStepInQueryString('setup-step'),
            ]);
    }

    public function save(): void
    {
        $team = $this->team();

        abort_unless($team !== null && auth()->user()?->belongsToTeam($team), 403);

        $data = $this->form->getState();
        $settings = $team->settings ?? [];

        $team->forceFill([
            'name' => $data['team_name'],
            'settings' => array_filter([
                ...$settings,
                'timezone' => $data['timezone'],
                'workflow' => $data['workflow'],
                'oauth_provider' => $data['oauth_provider'] ?? null,
                'oauth_client_id' => $data['oauth_client_id'] ?? null,
                'oauth_client_secret' => $data['oauth_client_secret'] ?? null,
                'api_key' => $data['api_key'] ?? null,
            ], static fn (mixed $value): bool => filled($value)),
        ])->save();

        auth()->user()->forceFill(['onboarding_completed_at' => now()])->save();

        Notification::make()
            ->title('Workspace setup complete')
            ->body('Your team is ready. You can update these details any time in Settings.')
            ->success()
            ->send();

        $this->redirect(Filament::getUrl());
    }

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()?->currentTeam !== null;
    }

    private function team(): ?Team
    {
        $tenant = Filament::getTenant() ?? auth()->user()?->currentTeam ?? auth()->user()?->latestTeam;

        return $tenant instanceof Team ? $tenant : null;
    }
}
