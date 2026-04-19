<x-filament-widgets::widget>
    <div class="space-y-6">
        <div
            class="flex items-center justify-between rounded-2xl bg-[linear-gradient(135deg,#002045_0%,#1a365d_100%)] px-5 py-3 text-white shadow-[0_12px_32px_rgba(11,28,48,0.16)]">
            <div class="flex items-center gap-3">
                <span class="rounded-full bg-white/10 p-2 text-[#8df5e4]">
                    <x-filament::icon icon="heroicon-o-shield-check" class="h-5 w-5" />
                </span>
                <span class="text-[10px] font-black uppercase tracking-[0.24em]">Restricted finance terminal —
                    authorized access only</span>
            </div>
            <span class="rounded-full bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em]">Ledger
                secure v4.0</span>
        </div>

        <div class="grid gap-6 xl:grid-cols-12">
            <section class="metric-hero xl:col-span-8 min-h-[15rem]">
                <div class="relative z-10 flex h-full flex-col justify-between">
                    <div>
                        <p class="mb-2 text-[10px] font-black uppercase tracking-[0.24em] text-white/65">Total ready to
                            pay</p>
                        <h3 class="text-4xl font-black tracking-[-0.03em] text-white xl:text-5xl">{{ $readyToPay }}</h3>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <span
                            class="rounded-xl bg-[#8df5e4] px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-[#00201c]">Batch
                            process all</span>
                        <span
                            class="rounded-xl border border-white/20 bg-white/10 px-4 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-white">Export
                            ledger report</span>
                    </div>
                </div>
            </section>

            <div class="space-y-6 xl:col-span-4">
                <div class="architectural-card border-l-4 border-[#43af9f]">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Approved invoices</p>
                    <p class="mt-2 text-3xl font-black text-[#0b1c30]">{{ $approvedInvoices }}</p>
                </div>

                <div class="architectural-card border-l-4 border-[#ba1a1a]">
                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[#43474e]">Flagged reviews</p>
                    <p class="mt-2 text-3xl font-black text-[#0b1c30]">{{ $flaggedReviews }}</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>