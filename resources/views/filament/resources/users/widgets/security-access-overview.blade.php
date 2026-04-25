<x-filament-widgets::widget>
    <div class="grid gap-8 xl:grid-cols-12">
        <section class="architectural-card xl:col-span-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Journal de sécurité</p>
                    <h3 class="text-2xl font-black tracking-[-0.02em] text-[#002045]">Activité récente des accès</h3>
                </div>
                <span
                    class="rounded-full bg-[#eff4ff] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#002045]">En
                    direct</span>
            </div>

            <div class="space-y-4">
                @foreach ($logs as $log)
                    <div
                        class="flex items-center justify-between rounded-2xl bg-[#eff4ff]/55 p-4 {{ $log['tone'] === 'danger' ? 'border-l-4 border-[#ba1a1a]' : ($log['tone'] === 'tertiary' ? 'border-l-4 border-[#43af9f]' : 'border-l-4 border-[#002045]') }}">
                        <div class="flex items-center gap-4">
                            <div class="team-avatar h-10! w-10! rounded-full! border-0!"
                                style="background: {{ $log['tone'] === 'danger' ? '#ffdad6' : ($log['tone'] === 'tertiary' ? '#8df5e4' : '#d6e3ff') }}">
                                {{ $log['initials'] }}
                            </div>
                            <div>
                                <p class="text-sm font-bold text-[#002045]">{{ $log['name'] }}</p>
                                <p class="text-xs text-[#57657a]">{{ $log['detail'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p
                                class="text-[10px] font-black uppercase tracking-[0.18em] {{ $log['tone'] === 'danger' ? 'text-[#ba1a1a]' : 'text-[#002045]' }}">
                                {{ $log['state'] }}
                            </p>
                            <p class="text-[10px] text-[#74777f]">{{ $log['time'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="architectural-card xl:col-span-4 bg-[#eff4ff]/65">
            <div class="space-y-4">
                <div class="rounded-2xl bg-white p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Personnel actif</p>
                    <p class="mt-2 text-4xl font-black text-[#002045]">{{ $metrics['active_personnel'] }}</p>
                </div>
                <div class="rounded-2xl bg-[linear-gradient(135deg,#003d36_0%,#005048_100%)] p-4 text-white">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/70">Conformité MFA</p>
                    <p class="mt-2 text-4xl font-black text-[#8df5e4]">{{ $metrics['mfa_compliance'] }}</p>
                </div>
                <div class="rounded-2xl bg-white p-4">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Révocations en attente
                    </p>
                    <p class="mt-2 text-4xl font-black text-[#ba1a1a]">{{ $metrics['pending_revocations'] }}</p>
                </div>
            </div>
        </section>
    </div>
</x-filament-widgets::widget>