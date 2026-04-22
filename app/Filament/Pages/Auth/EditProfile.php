<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EditProfile extends BaseEditProfile
{
    protected static ?string $title = 'Paramètres du Profil';

    protected string $view = 'filament.pages.auth.edit-profile';

    // ── Personal info ─────────────────────────────────────────────────────────

    public string $profileName       = '';
    public string $profileEmail      = '';
    public string $profilePhone      = '';
    public string $profileDepartment = '';

    // ── Preferences ───────────────────────────────────────────────────────────

    public string $prefLanguage      = 'fr';
    public string $prefTheme         = 'light';
    public bool   $prefNotifications = true;

    // ── Password change ───────────────────────────────────────────────────────

    public string $currentPassword      = '';
    public string $newPassword          = '';
    public string $newPasswordConfirm   = '';

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    public function mount(): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $this->profileName       = $user->name       ?? '';
        $this->profileEmail      = $user->email      ?? '';
        $this->profilePhone      = $user->phone      ?? '';
        $this->profileDepartment = $user->department ?? '';

        $prefs = $user->preferences ?? [];
        $this->prefLanguage      = $prefs['language']      ?? 'fr';
        $this->prefTheme         = $prefs['theme']         ?? 'light';
        $this->prefNotifications = (bool) ($prefs['notifications'] ?? true);
    }

    // ── Save personal info ────────────────────────────────────────────────────

    public function saveProfile(): void
    {
        $data = $this->validate([
            'profileName'       => ['required', 'string', 'max:255'],
            'profileEmail'      => ['required', 'email', 'max:255'],
            'profilePhone'      => ['nullable', 'string', 'max:50'],
            'profileDepartment' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->update([
            'name'       => $data['profileName'],
            'email'      => $data['profileEmail'],
            'phone'      => $data['profilePhone'],
            'department' => $data['profileDepartment'],
        ]);

        Notification::make()
            ->title('Profil mis à jour')
            ->success()
            ->send();
    }

    // ── Save preferences ──────────────────────────────────────────────────────

    public function savePreferences(): void
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $user->update([
            'preferences' => [
                'language'      => $this->prefLanguage,
                'theme'         => $this->prefTheme,
                'notifications' => $this->prefNotifications,
            ],
        ]);

        Notification::make()
            ->title('Préférences enregistrées')
            ->success()
            ->send();
    }

    // ── Change password ───────────────────────────────────────────────────────

    public function changePassword(): void
    {
        $this->validate([
            'currentPassword'    => ['required', 'string'],
            'newPassword'        => ['required', 'string', 'min:8', 'same:newPasswordConfirm'],
            'newPasswordConfirm' => ['required', 'string'],
        ]);

        /** @var \App\Models\User $user */
        $user = auth()->user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Le mot de passe actuel est incorrect.');
            return;
        }

        $user->update(['password' => $this->newPassword]);

        $this->currentPassword    = '';
        $this->newPassword        = '';
        $this->newPasswordConfirm = '';

        Notification::make()
            ->title('Mot de passe modifié')
            ->success()
            ->send();
    }

    // ── Sessions ──────────────────────────────────────────────────────────────

    public function getActiveSessions(): array
    {
        $currentId = session()->getId();

        return DB::table('sessions')
            ->where('user_id', auth()->id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(function (object $row) use ($currentId): array {
                $ua       = $row->user_agent ?? '';
                $isMobile = preg_match('/Mobile|Android|iPhone|iPad/i', $ua) === 1;
                $isTablet = preg_match('/iPad|Tablet/i', $ua) === 1;

                $device = $isTablet ? 'tablet' : ($isMobile ? 'mobile' : 'desktop');

                $browser = 'Navigateur';
                if (str_contains($ua, 'Chrome'))  $browser = 'Chrome';
                if (str_contains($ua, 'Firefox')) $browser = 'Firefox';
                if (str_contains($ua, 'Safari') && ! str_contains($ua, 'Chrome')) $browser = 'Safari';
                if (str_contains($ua, 'Edge'))    $browser = 'Edge';

                $os = 'OS inconnu';
                if (str_contains($ua, 'Windows')) $os = 'Windows';
                if (str_contains($ua, 'Mac'))     $os = 'macOS';
                if (str_contains($ua, 'Linux'))   $os = 'Linux';
                if (str_contains($ua, 'Android')) $os = 'Android';
                if (str_contains($ua, 'iOS') || str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $os = 'iOS';

                $lastActivity = $row->last_activity;
                $diff         = now()->timestamp - $lastActivity;
                $timeAgo = match (true) {
                    $diff < 60      => 'À l\'instant',
                    $diff < 3600    => 'Il y a ' . floor($diff / 60) . ' min',
                    $diff < 86400   => 'Il y a ' . floor($diff / 3600) . 'h',
                    default         => 'Il y a ' . floor($diff / 86400) . ' j',
                };

                return [
                    'id'        => $row->id,
                    'device'    => $device,
                    'name'      => ucfirst($device) . ' · ' . $browser,
                    'details'   => $browser . ' · ' . $os . ' · IP: ' . ($row->ip_address ?? '—'),
                    'time'      => $timeAgo,
                    'current'   => $row->id === $currentId,
                ];
            })
            ->toArray();
    }

    public function disconnectSession(string $sessionId): void
    {
        if ($sessionId === session()->getId()) {
            return;
        }

        DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', auth()->id())
            ->delete();

        Notification::make()
            ->title('Session déconnectée')
            ->success()
            ->send();
    }

    public function disconnectAllSessions(): void
    {
        DB::table('sessions')
            ->where('user_id', auth()->id())
            ->where('id', '!=', session()->getId())
            ->delete();

        Notification::make()
            ->title('Toutes les autres sessions ont été déconnectées')
            ->success()
            ->send();
    }

    // ── Keep Filament base form empty (UI handled in custom view) ─────────────

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }
}
