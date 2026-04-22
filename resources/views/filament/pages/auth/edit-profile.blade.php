<x-filament-panels::page>
    <div class="space-y-8">

        {{-- ── PAGE HEADER ──────────────────────────────────────────────────────── --}}
        <section class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-3xl font-black tracking-[-0.03em] text-[#002045]">Paramètres du Profil</h2>
                <p class="mt-1 text-sm font-medium text-[#57657a]">
                    Gérez votre identité numérique et vos préférences au sein de l'écosystème Fiscal Control.
                </p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <button type="button" wire:click="$refresh"
                    class="rounded-xl border border-[#c4c6cf]/60 bg-white px-5 py-2.5 text-sm font-bold text-[#43474e] shadow-sm transition hover:bg-[#f8faff]">
                    Annuler
                </button>
                <button type="button" wire:click="saveProfile"
                    class="rounded-xl bg-[#002045] px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-[#1a365d]">
                    Enregistrer les modifications
                </button>
            </div>
        </section>

        {{-- ── TOP GRID: Personal info + Preferences ───────────────────────────── --}}
        <section class="grid gap-6 xl:grid-cols-[1fr_340px]">

            {{-- Personal Information ──────────────────────────────────────────── --}}
            <div class="rounded-3xl border border-l-4 border-[#c4c6cf]/20 border-l-[#002045] bg-white p-8 shadow-sm">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-[#002045]">Informations Personnelles</h3>
                        <p class="text-[11px] font-medium text-[#57657a]">Détails de votre compte administrateur</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#eff4ff]">
                        <svg class="h-5 w-5 text-[#002045]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                        </svg>
                    </div>
                </div>

                <div class="flex flex-col gap-8 md:flex-row md:items-start">

                    {{-- Avatar ───────────────────────────────────────────────── --}}
                    <div class="flex shrink-0 flex-col items-center gap-3">
                        <div class="relative">
                            @php $user = auth()->user(); @endphp
                            <div class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-2xl bg-[#eff4ff]">
                                <span class="text-3xl font-black text-[#002045]">
                                    {{ strtoupper(substr($user->name ?? 'U', 0, 2)) }}
                                </span>
                            </div>
                            <button type="button"
                                class="absolute bottom-1.5 right-1.5 flex h-7 w-7 items-center justify-center rounded-full border border-[#c4c6cf]/40 bg-white shadow-md transition hover:bg-[#eff4ff]"
                                title="Modifier l'avatar">
                                <svg class="h-3.5 w-3.5 text-[#43474e]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                </svg>
                            </button>
                        </div>
                        <div class="text-center">
                            <p class="text-sm font-black text-[#002045]">{{ $user->getRoleNames()->first() ?? 'Utilisateur' }}</p>
                            <p class="text-[11px] font-medium text-[#57657a]">
                                {{ $user->getAllPermissions()->count() }} permission(s)
                            </p>
                        </div>
                    </div>

                    {{-- Fields ───────────────────────────────────────────────── --}}
                    <div class="flex-1 space-y-5">
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">
                                Nom complet
                            </label>
                            <input type="text" wire:model="profileName"
                                class="w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-4 py-3 text-sm font-medium text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10"
                                placeholder="Votre nom complet" />
                            @error('profileName')
                                <p class="mt-1 text-[11px] text-[#ba1a1a]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">
                                Adresse email
                            </label>
                            <input type="email" wire:model="profileEmail"
                                class="w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-4 py-3 text-sm font-medium text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10"
                                placeholder="votre@email.com" />
                            @error('profileEmail')
                                <p class="mt-1 text-[11px] text-[#ba1a1a]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">
                                Téléphone
                            </label>
                            <input type="tel" wire:model="profilePhone"
                                class="w-full rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-4 py-3 text-sm font-medium text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10"
                                placeholder="+000 00 000 000" />
                            @error('profilePhone')
                                <p class="mt-1 text-[11px] text-[#ba1a1a]">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">
                                Département
                            </label>
                            <div class="relative">
                                <select wire:model="profileDepartment"
                                    class="w-full appearance-none rounded-xl border border-[#c4c6cf]/60 bg-[#f8faff] px-4 py-3 text-sm font-medium text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10">
                                    <option value="">— Sélectionner un département —</option>
                                    <option value="Direction Financière">Direction Financière</option>
                                    <option value="Comptabilité">Comptabilité</option>
                                    <option value="Contrôle de gestion">Contrôle de gestion</option>
                                    <option value="Audit interne">Audit interne</option>
                                    <option value="Direction générale">Direction générale</option>
                                    <option value="Ressources Humaines">Ressources Humaines</option>
                                    <option value="Informatique">Informatique</option>
                                    <option value="Commercial">Commercial</option>
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#74777f]"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7" />
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Preferences ───────────────────────────────────────────────────── --}}
            <div class="rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
                <div class="mb-6">
                    <h3 class="text-lg font-black text-[#002045]">Préférences</h3>
                    <p class="text-[11px] font-medium text-[#57657a]">Personnalisez votre interface</p>
                </div>

                <div class="space-y-6">
                    {{-- Language --}}
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#eff4ff]">
                                <svg class="h-5 w-5 text-[#002045]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-[#002045]">Langue</p>
                                <p class="text-[11px] font-medium text-[#57657a]">Interface globale</p>
                            </div>
                        </div>
                        <div class="flex overflow-hidden rounded-xl border border-[#c4c6cf]/40">
                            <button type="button" wire:click="$set('prefLanguage', 'fr')"
                                class="px-4 py-2 text-xs font-black transition {{ $prefLanguage === 'fr' ? 'bg-[#002045] text-white' : 'bg-white text-[#57657a] hover:bg-[#f8faff]' }}">
                                FR
                            </button>
                            <button type="button" wire:click="$set('prefLanguage', 'en')"
                                class="px-4 py-2 text-xs font-black transition {{ $prefLanguage === 'en' ? 'bg-[#002045] text-white' : 'bg-white text-[#57657a] hover:bg-[#f8faff]' }}">
                                EN
                            </button>
                        </div>
                    </div>

                    <div class="h-px bg-[#c4c6cf]/20"></div>

                    {{-- Theme --}}
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#eff4ff]">
                                <svg class="h-5 w-5 text-[#002045]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-[#002045]">Thème</p>
                                <p class="text-[11px] font-medium text-[#57657a]">Apparence visuelle</p>
                            </div>
                        </div>
                        <div class="flex overflow-hidden rounded-xl border border-[#c4c6cf]/40">
                            <button type="button" wire:click="$set('prefTheme', 'light')"
                                class="flex items-center justify-center px-3 py-2 transition {{ $prefTheme === 'light' ? 'bg-[#002045] text-white' : 'bg-white text-[#57657a] hover:bg-[#f8faff]' }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                                </svg>
                            </button>
                            <button type="button" wire:click="$set('prefTheme', 'dark')"
                                class="flex items-center justify-center px-3 py-2 transition {{ $prefTheme === 'dark' ? 'bg-[#002045] text-white' : 'bg-white text-[#57657a] hover:bg-[#f8faff]' }}">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                    stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="h-px bg-[#c4c6cf]/20"></div>

                    {{-- Notifications --}}
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#eff4ff]">
                                <svg class="h-5 w-5 text-[#002045]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-[#002045]">Notifications</p>
                                <p class="text-[11px] font-medium text-[#57657a]">Alertes de clôture</p>
                            </div>
                        </div>
                        <button type="button" wire:click="$set('prefNotifications', {{ $prefNotifications ? 'false' : 'true' }})"
                            class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition-colors duration-200 {{ $prefNotifications ? 'bg-[#002045]' : 'bg-[#c4c6cf]/60' }}">
                            <span
                                class="inline-block h-5 w-5 transform rounded-full bg-white shadow-sm transition-transform duration-200 {{ $prefNotifications ? 'translate-x-5' : 'translate-x-0.5' }}">
                            </span>
                        </button>
                    </div>

                    <button type="button" wire:click="savePreferences"
                        class="w-full rounded-xl bg-[#eff4ff] px-4 py-2.5 text-xs font-black uppercase tracking-[0.14em] text-[#002045] transition hover:bg-[#d6e3ff]">
                        Appliquer les préférences
                    </button>
                </div>
            </div>
        </section>

        {{-- ── BOTTOM GRID: Security + Active sessions ──────────────────────── --}}
        <section class="grid gap-6 xl:grid-cols-2">

            {{-- Security ──────────────────────────────────────────────────────── --}}
            <div class="rounded-3xl border border-l-4 border-[#c4c6cf]/20 border-l-[#ba1a1a] bg-white p-8 shadow-sm">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-[#002045]">Sécurité</h3>
                        <p class="text-[11px] font-medium text-[#57657a]">Protections de compte actives</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#fde8d8]">
                        <svg class="h-5 w-5 text-[#ba1a1a]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                        </svg>
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- 2FA --}}
                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-[#f0fdf4] px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#dcfce7]">
                                <svg class="h-4 w-4 text-[#16a34a]" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-black text-[#002045]">2FA (Double Facteur)</p>
                                <p class="text-[11px] font-medium text-[#57657a]">Activé · Via Microsoft
                                    Authenticator</p>
                            </div>
                        </div>
                        <button type="button"
                            class="rounded-lg border border-[#c4c6cf]/40 bg-white px-3 py-1.5 text-[11px] font-black text-[#002045] shadow-sm transition hover:bg-[#eff4ff]">
                            Modifier
                        </button>
                    </div>

                    {{-- Change password --}}
                    <div x-data="{ open: false }">
                        <button type="button"
                            @click="open = !open"
                            class="flex w-full items-center justify-between rounded-2xl border border-[#c4c6cf]/40 px-5 py-4 transition hover:bg-[#f8faff]">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#eff4ff]">
                                    <svg class="h-4 w-4 text-[#002045]" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <span class="text-sm font-black text-[#002045]">Changer le mot de passe</span>
                            </div>
                            <svg class="h-4 w-4 text-[#74777f] transition-transform" :class="{ 'rotate-90': open }"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                            </svg>
                        </button>

                        {{-- Password form (toggled) --}}
                        <div x-show="open" x-cloak class="mt-3 space-y-3 rounded-2xl border border-[#c4c6cf]/30 bg-[#f8faff] p-5">
                            <div>
                                <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Mot de passe actuel</label>
                                <input type="password" wire:model="currentPassword"
                                    class="w-full rounded-xl border border-[#c4c6cf]/60 bg-white px-4 py-2.5 text-sm font-medium text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10" />
                                @error('currentPassword')
                                    <p class="mt-1 text-[11px] text-[#ba1a1a]">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Nouveau mot de passe</label>
                                <input type="password" wire:model="newPassword"
                                    class="w-full rounded-xl border border-[#c4c6cf]/60 bg-white px-4 py-2.5 text-sm font-medium text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10" />
                                @error('newPassword')
                                    <p class="mt-1 text-[11px] text-[#ba1a1a]">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1 block text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Confirmer le nouveau mot de passe</label>
                                <input type="password" wire:model="newPasswordConfirm"
                                    class="w-full rounded-xl border border-[#c4c6cf]/60 bg-white px-4 py-2.5 text-sm font-medium text-[#0b1c30] outline-none transition focus:border-[#002045] focus:ring-2 focus:ring-[#002045]/10" />
                                @error('newPasswordConfirm')
                                    <p class="mt-1 text-[11px] text-[#ba1a1a]">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="button" wire:click="changePassword"
                                class="w-full rounded-xl bg-[#002045] px-4 py-2.5 text-xs font-black text-white transition hover:bg-[#1a365d]">
                                Mettre à jour le mot de passe
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active Sessions ────────────────────────────────────────────────── --}}
            <div class="rounded-3xl border border-[#c4c6cf]/20 bg-white p-8 shadow-sm">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-black text-[#002045]">Sessions Actives</h3>
                        <p class="text-[11px] font-medium text-[#57657a]">Appareils connectés à votre compte</p>
                    </div>
                    <button type="button" wire:click="disconnectAllSessions"
                        class="text-[11px] font-black text-[#ba1a1a] transition hover:underline">
                        Déconnecter tout
                    </button>
                </div>

                @php $sessions = $this->getActiveSessions(); @endphp

                @if (empty($sessions))
                    <p class="text-sm text-[#57657a]">Aucune session active trouvée.</p>
                @else
                    <div class="space-y-4">
                        @foreach ($sessions as $session)
                            <div class="flex items-center justify-between gap-4 rounded-2xl border border-[#c4c6cf]/30 px-4 py-3 transition hover:bg-[#f8faff]">
                                <div class="flex items-center gap-3">
                                    {{-- Device icon --}}
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#eff4ff]">
                                        @if ($session['device'] === 'mobile')
                                            <svg class="h-5 w-5 text-[#002045]" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3" />
                                            </svg>
                                        @elseif ($session['device'] === 'tablet')
                                            <svg class="h-5 w-5 text-[#002045]" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M10.5 19.5h3m-6.75 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-15a2.25 2.25 0 0 0-2.25-2.25H6.75A2.25 2.25 0 0 0 4.5 4.5v15a2.25 2.25 0 0 0 2.25 2.25Z" />
                                            </svg>
                                        @else
                                            <svg class="h-5 w-5 text-[#002045]" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25A2.25 2.25 0 0 1 5.25 3h13.5A2.25 2.25 0 0 1 21 5.25Z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-black text-[#002045]">{{ $session['name'] }}</p>
                                            @if ($session['current'])
                                                <span class="rounded-full bg-[#dff7f0] px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.14em] text-[#005048]">Actuelle</span>
                                            @endif
                                        </div>
                                        <p class="mt-0.5 truncate text-[11px] font-medium text-[#57657a]">{{ $session['details'] }}</p>
                                    </div>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="text-[11px] font-medium text-[#74777f]">{{ $session['time'] }}</span>
                                    @if (! $session['current'])
                                        <button type="button"
                                            wire:click="disconnectSession('{{ $session['id'] }}')"
                                            class="flex h-7 w-7 items-center justify-center rounded-full text-[#ba1a1a] transition hover:bg-[#fde8d8]">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </section>
    </div>
</x-filament-panels::page>
