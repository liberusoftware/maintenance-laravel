<x-filament-panels::page>
    <div class="mx-auto w-full max-w-4xl">
        <div class="mb-6 rounded-2xl bg-gradient-to-br from-primary-600 to-primary-800 px-6 py-7 text-white shadow-sm sm:px-8">
            <div class="flex items-start gap-4">
                <div class="rounded-xl bg-white/15 p-3">
                    <x-filament::icon icon="heroicon-o-sparkles" class="h-7 w-7" />
                </div>
                <div>
                    <p class="text-sm font-medium text-white/75">A few quick steps</p>
                    <h2 class="mt-1 text-2xl font-semibold tracking-tight">Make this workspace yours</h2>
                    <p class="mt-2 max-w-2xl text-sm leading-6 text-white/80">Set your team defaults and connect the services you need. You can skip optional connections and return here whenever you like.</p>
                </div>
            </div>
        </div>

        <form wire:submit="save">
            {{ $this->form }}

            <div class="mt-6 flex justify-end">
                <x-filament::button type="submit" icon="heroicon-m-arrow-right">
                    Finish setup
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
