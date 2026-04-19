<x-filament-widgets::widget>
    <div class="space-y-8">
        <div class="grid gap-6 xl:grid-cols-3">
            <section class="architectural-card xl:col-span-2">
                <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Architectural
                            Ledger</p>
                        <h3 class="mt-1 text-2xl font-black tracking-[-0.02em] text-[#002045]">Recent Ledger Activity
                        </h3>
                        <p class="text-sm text-[#43474e]">A premium operational view of invoices, quotes, payments, and
                            expenses.</p>
                    </div>

                    <div
                        class="inline-flex items-center rounded-full bg-[#eff4ff] px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-[#002045]">
                        Live snapshot
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-160 border-separate border-spacing-y-2">
                        <thead>
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-[0.2em] text-[#002045]">
                                    Reference</th>
                                <th
                                    class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-[0.2em] text-[#002045]">
                                    Category</th>
                                <th
                                    class="px-4 py-2 text-right text-[10px] font-black uppercase tracking-[0.2em] text-[#002045]">
                                    Value</th>
                                <th
                                    class="px-4 py-2 text-left text-[10px] font-black uppercase tracking-[0.2em] text-[#002045]">
                                    Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $entry)
                                <tr class="ledger-row">
                                    <td class="relative rounded-l-2xl bg-[#eff4ff]/55 px-4 py-4">
                                        <span class="absolute inset-y-3 left-0 w-1 rounded-r-full"
                                            style="background: {{ $entry['pillar'] }}"></span>

                                        <div class="pl-3">
                                            <div class="text-[11px] font-black uppercase tracking-[0.18em] text-[#002045]">
                                                {{ $entry['reference'] }}</div>
                                            <div class="mt-1 text-sm font-bold text-[#002045]">{{ $entry['entity'] }}</div>
                                            <div class="text-xs text-[#43474e]">{{ $entry['meta'] }}</div>
                                        </div>
                                    </td>

                                    <td class="bg-[#eff4ff]/55 px-4 py-4">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                            style="background: {{ $entry['category_bg'] }}; color: {{ $entry['category_fg'] }};">
                                            {{ $entry['category'] }}
                                        </span>
                                    </td>

                                    <td class="bg-[#eff4ff]/55 px-4 py-4 text-right text-sm font-black text-[#002045]">
                                        {{ $entry['value'] }}
                                    </td>

                                    <td class="rounded-r-2xl bg-[#eff4ff]/55 px-4 py-4">
                                        <span
                                            class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-bold"
                                            style="background: {{ $entry['status_bg'] }}; color: {{ $entry['status_fg'] }};">
                                            <span class="h-2 w-2 rounded-full"
                                                style="background: {{ $entry['status_dot'] }}"></span>
                                            {{ $entry['status'] }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="space-y-6">
                <section
                    class="rounded-[1.25rem] bg-[linear-gradient(135deg,#002045_0%,#1a365d_100%)] p-6 text-white shadow-[0_16px_40px_rgba(11,28,48,0.16)]">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.24em] text-white/60">Delivery wing
                            </p>
                            <h3 class="text-lg font-black">Active Milestones</h3>
                        </div>
                        <span
                            class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#8df5e4]">Projects</span>
                    </div>

                    <div class="space-y-5">
                        @foreach ($milestones as $milestone)
                            <div>
                                <div class="mb-2 flex items-end justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-white/60">
                                            {{ $milestone['name'] }}</p>
                                        <p class="text-sm text-white">{{ $milestone['stage'] }}</p>
                                    </div>
                                    <span class="text-xs font-black">{{ $milestone['progress'] }}%</span>
                                </div>

                                <div class="h-2 overflow-hidden rounded-full bg-white/10">
                                    <div class="h-full rounded-full"
                                        style="width: {{ $milestone['progress'] }}%; background: {{ $milestone['color'] }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="architectural-card">
                    <div class="mb-5">
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">System health</p>
                        <h3 class="text-lg font-black tracking-[-0.02em] text-[#002045]">Commercial pulse</h3>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-1">
                        @foreach ($health as $item)
                            <div class="rounded-2xl bg-[#eff4ff]/60 p-4">
                                <div class="flex items-end justify-between gap-4">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">
                                            {{ $item['label'] }}</p>
                                        <p class="text-xl font-black text-[#002045]">{{ $item['value'] }}</p>
                                    </div>
                                </div>

                                <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full"
                                        style="width: {{ $item['progress'] }}%; background: {{ $item['color'] }}"></div>
                                </div>

                                <p class="mt-2 text-xs text-[#43474e]">{{ $item['note'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>

        <section class="architectural-card board-shell overflow-hidden">
            <div class="mb-8 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="mb-2 flex gap-2 text-[10px] font-black uppercase tracking-[0.24em] text-[#86a0cd]">
                        <span>Projects</span>
                        <span>/</span>
                        <span class="text-[#002045]">Delivery Board</span>
                    </div>
                    <h3 class="text-3xl font-black tracking-[-0.03em] text-[#002045]">
                        {{ $boardSummary['organization'] }}</h3>
                </div>

                <div class="flex items-center gap-3">
                    <div class="flex -space-x-2">
                        @foreach ($team as $member)
                            <div class="team-avatar" style="background: {{ $member['tone'] }}"
                                title="{{ $member['name'] }}">
                                {{ $member['initials'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                @foreach ($board as $column)
                    <div class="board-column">
                        <div class="mb-4 flex items-center justify-between px-1">
                            <div class="flex items-center gap-3">
                                <span class="h-2 w-2 rounded-full" style="background: {{ $column['dot'] }}"></span>
                                <h4 class="text-xs font-black uppercase tracking-[0.24em] text-[#43474e]">
                                    {{ $column['label'] }}</h4>
                                <span class="rounded-md px-2 py-0.5 text-[10px] font-bold"
                                    style="background: {{ $column['badge_bg'] }}; color: {{ $column['badge_fg'] }};">
                                    {{ $column['count'] }}
                                </span>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @foreach ($column['items'] as $item)
                                <article class="board-project-card" style="border-left: 4px solid {{ $item['accent'] }};">
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <span
                                            class="rounded-md bg-[#eff4ff] px-2 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#002045]">
                                            {{ $item['category'] }}
                                        </span>
                                        <span
                                            class="text-[10px] font-bold uppercase tracking-[0.12em] text-[#74777f]">{{ $item['reference'] }}</span>
                                    </div>

                                    <div class="flex items-start justify-between gap-2">
                                        <h5 class="text-base font-black leading-tight text-[#002045]">{{ $item['title'] }}</h5>
                                        @if ($item['done'])
                                            <span
                                                class="rounded-full bg-[#dff8f0] px-2 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#005048]">Done</span>
                                        @endif
                                    </div>

                                    <p class="mt-2 text-xs font-medium text-[#43474e]">Client: {{ $item['client'] }}</p>
                                    <p class="mt-2 text-xs text-[#57657a]">{{ $item['description'] }}</p>

                                    @if ($item['show_progress'])
                                        <div class="mt-4 space-y-1.5">
                                            <div
                                                class="flex justify-between text-[10px] font-black uppercase tracking-[0.14em] text-[#74777f]">
                                                <span>Progress</span>
                                                <span class="text-[#002045]">{{ $item['progress'] }}%</span>
                                            </div>
                                            <div class="h-1.5 overflow-hidden rounded-full bg-[#e5eeff]">
                                                <div class="h-full rounded-full"
                                                    style="width: {{ $item['progress'] }}%; background: {{ $item['accent'] }}">
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mt-4 flex items-center justify-between border-t border-[#eff4ff] pt-4">
                                        <div class="flex items-center gap-2">
                                            <div class="team-avatar team-avatar-sm" style="background: #eff4ff">
                                                {{ $item['initials'] }}</div>
                                            <span class="text-[11px] font-bold text-[#0b1c30]">{{ $item['assignee'] }}</span>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-[10px] font-black uppercase tracking-[0.12em] text-[#74777f]">
                                                {{ $item['done'] ? 'Finished' : 'Deadline' }}</p>
                                            <p class="text-[11px] font-black text-[#002045]">{{ $item['due'] }}</p>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        <div class="grid gap-8 xl:grid-cols-12">
            <section class="metric-hero xl:col-span-8">
                <div class="relative z-10 flex w-full items-end justify-between gap-6">
                    <div>
                        <p class="mb-4 text-xs font-black uppercase tracking-[0.24em] text-[#8df5e4]">Board performance
                        </p>
                        <h3 class="max-w-xl text-4xl font-black tracking-[-0.03em] text-white xl:text-5xl">
                            {{ $boardSummary['headline'] }}</h3>
                    </div>

                    <div class="text-right text-white">
                        <div class="text-6xl font-black tracking-[-0.04em]">{{ $boardSummary['active_contracts'] }}
                        </div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/60">Active contracts
                        </div>
                    </div>
                </div>
            </section>

            <section class="architectural-card xl:col-span-4 bg-[#dce9ff]/55">
                <div class="mb-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Critical actions</p>
                    <h3 class="text-lg font-black tracking-[-0.02em] text-[#002045]">Operational review</h3>
                </div>

                <div class="space-y-3">
                    @foreach ($actions as $action)
                        <div class="glass-panel flex items-start gap-3 rounded-2xl p-4">
                            <span
                                class="mt-1 h-2.5 w-2.5 rounded-full {{ $action['tone'] === 'danger' ? 'bg-[#ba1a1a]' : ($action['tone'] === 'success' ? 'bg-[#43af9f]' : 'bg-[#455f88]') }}"></span>
                            <div>
                                <p class="text-xs font-bold text-[#0b1c30]">{{ $action['title'] }}</p>
                                <p class="text-[10px] text-[#74777f]">{{ $action['note'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <button
                    class="mt-6 w-full rounded-2xl border-2 border-[#002045] px-4 py-3 text-[10px] font-black uppercase tracking-[0.2em] text-[#002045] transition hover:bg-[#002045] hover:text-white">
                    View complete audit log
                </button>
            </section>
        </div>
    </div>
</x-filament-widgets::widget>