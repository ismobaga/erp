<x-filament-panels::page>
    <div class="space-y-8">
        <div class="grid gap-8 xl:grid-cols-3">
            <section class="xl:col-span-2 space-y-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Notification hub
                        </p>
                        <h2 class="text-3xl font-black tracking-[-0.03em] text-[#002045]">Overdue invoices</h2>
                    </div>
                    <span
                        class="rounded-full bg-[#ffdad6] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#93000a]">Priority</span>
                </div>

                @foreach ($overdueInvoices as $invoice)
                    <article class="architectural-card relative overflow-hidden">
                        <span class="absolute inset-y-4 left-0 w-1 rounded-r-full bg-[#ba1a1a]"></span>
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div class="pl-3">
                                <h3 class="text-lg font-black text-[#002045]">{{ $invoice['reference'] }} ·
                                    {{ $invoice['client'] }}</h3>
                                <p class="mt-1 text-sm text-[#57657a]">{{ $invoice['note'] }}</p>
                                <div class="mt-3 flex flex-wrap gap-3">
                                    <span class="text-2xl font-black text-[#ba1a1a]">{{ $invoice['amount'] }}</span>
                                    <span
                                        class="rounded-full bg-[#eff4ff] px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[#43474e]">{{ $invoice['age'] }}</span>
                                </div>
                            </div>
                            <span
                                class="rounded-xl bg-[#8df5e4] px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-[#00201c]">Send
                                reminder</span>
                        </div>
                    </article>
                @endforeach
            </section>

            <section class="space-y-6">
                <div class="architectural-card bg-[#eff4ff]/70">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-lg font-black text-[#002045]">Flagged payments</h3>
                        <span
                            class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">{{ count($flaggedPayments) }}
                            new</span>
                    </div>
                    <div class="space-y-4">
                        @foreach ($flaggedPayments as $payment)
                            <div class="rounded-2xl bg-white p-4 border-l-4 border-[#43af9f]">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#005048]">Needs review
                                </p>
                                <p class="mt-1 text-sm font-black text-[#002045]">{{ $payment['title'] }}</p>
                                <p class="text-xs text-[#57657a]">{{ $payment['client'] }}</p>
                                <p class="mt-2 text-xs text-[#57657a]">{{ $payment['note'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div
                    class="rounded-[1.5rem] bg-[linear-gradient(135deg,#002045_0%,#1a365d_100%)] p-6 text-white shadow-[0_16px_40px_rgba(11,28,48,0.16)]">
                    <h3 class="text-lg font-black">Total system health</h3>
                    <div class="mt-5 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/60">Open alerts</p>
                            <p class="text-2xl font-black">{{ $health['open_alerts'] }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/60">Exposure</p>
                            <p class="text-2xl font-black text-[#8df5e4]">{{ $health['exposure'] }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/60">Resolved today
                            </p>
                            <p class="text-2xl font-black">{{ $health['resolved'] }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-black uppercase tracking-[0.18em] text-white/60">Efficiency</p>
                            <p class="text-2xl font-black">{{ $health['efficiency'] }}</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <section class="architectural-card bg-[#eff4ff]/45">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.24em] text-[#43474e]">Audit feed</p>
                    <h3 class="text-2xl font-black tracking-[-0.03em] text-[#002045]">Recent reconciliation activity
                    </h3>
                </div>
                <span
                    class="rounded-full bg-white px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[#002045]">Live
                    audit active</span>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($feed as $item)
                    <div class="rounded-2xl bg-white p-4 shadow-sm">
                        <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">{{ $item['meta'] }}</p>
                        <p class="mt-2 text-sm font-black text-[#002045]">{{ $item['label'] }}</p>
                        <p class="mt-1 text-xs text-[#74777f]">{{ $item['time'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>
</x-filament-panels::page>