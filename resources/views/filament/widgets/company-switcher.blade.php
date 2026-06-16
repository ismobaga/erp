<x-filament-widgets::widget class="fi-wi-company-switcher">
    @if(count($companies) <= 1)
        <div class="flex items-center gap-2 px-2 py-1.5">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-[#eff4ff]">
                <x-filament::icon icon="heroicon-o-building-office-2" class="h-3.5 w-3.5 text-[#002045]" />
            </span>
            <span class="text-xs font-bold text-[#0b1c30] dark:text-[#edf4ff]">
                {{ $currentCompany?->name ?? __('No company') }}
            </span>
        </div>
    @else
        <div class="relative flex items-center gap-2 rounded-xl bg-[#eff4ff]/60 px-3 py-2 dark:bg-white/5">
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-white shadow-sm dark:bg-white/10">
                <x-filament::icon icon="heroicon-o-building-office-2" class="h-3.5 w-3.5 text-[#002045] dark:text-[#8df5e4]" />
            </span>
            <select
                wire:change="switchTo($event.target.value)"
                class="flex-1 appearance-none bg-transparent text-xs font-bold text-[#002045] outline-none cursor-pointer dark:text-[#edf4ff]"
            >
                @foreach($companies as $company)
                    <option
                        value="{{ $company->id }}"
                        @selected($currentCompany?->id === $company->id)
                    >
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            <x-filament::icon icon="heroicon-m-chevron-up-down" class="h-3.5 w-3.5 shrink-0 text-[#57657a] pointer-events-none" />
        </div>
    @endif
</x-filament-widgets::widget>
