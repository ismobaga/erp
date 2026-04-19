<x-filament-widgets::widget>
    <div class="grid gap-6 xl:grid-cols-3">
        <section class="architectural-card xl:col-span-2">
            <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Architectural Ledger
                    </p>
                    <h3 class="mt-1 text-2xl font-black tracking-[-0.02em] text-[#002045]">Recent Ledger Activity</h3>
                    <p class="text-sm text-[#43474e]">A premium operational view of invoices, quotes, payments, and
                        expenses.</p>
                </div>

                <div
                    class="inline-flex items-center rounded-full bg-[#eff4ff] px-3 py-1 text-[10px] font-black uppercase tracking-[0.24em] text-[#002045]">
                    Live snapshot
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] border-separate border-spacing-y-2">
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
                                <td class="relative rounded-l-[1rem] bg-[#eff4ff]/55 px-4 py-4">
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

                                <td class="rounded-r-[1rem] bg-[#eff4ff]/55 px-4 py-4">
                                    <span class="inline-flex items-center gap-2 rounded-full px-2.5 py-1 text-xs font-bold"
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
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-white/60">Delivery wing</p>
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
                        <div class="rounded-[1rem] bg-[#eff4ff]/60 p-4">
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
</x-filament-widgets::widget>