<x-filament-widgets::widget class="fi-wi-company-switcher">
    <div class="flex items-center gap-2 px-4 py-2">
        @if(count($companies) <= 1)
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ $currentCompany?->name ?? __('No company') }}
            </span>
        @else
            <div class="flex items-center gap-2">
                <x-filament::icon
                    icon="heroicon-o-building-office-2"
                    class="h-4 w-4 text-gray-500 dark:text-gray-400"
                />
                <select
                    wire:change="switchTo($event.target.value)"
                    class="text-sm font-medium text-gray-700 dark:text-gray-300 bg-transparent border-0 cursor-pointer focus:ring-0 focus:outline-none"
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
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
