@props([
    'name',
    'value' => '',
    'placeholder' => 'Semua',
    'icon' => 'list-filter',
    'options' => [],
    'submit' => true,
])

@php
    $currentLabel = $placeholder;
    foreach ($options as $opt) {
        if ((string) ($opt['value'] ?? '') === (string) $value && (string) $value !== '') {
            $currentLabel = $opt['label'];
            break;
        }
    }
    $sq = fn ($v) => str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $v);
@endphp

<div
    x-data="{ open: false, val: '{{ $sq($value) }}', label: '{{ $sq($currentLabel) }}', select(newVal, newLabel) { this.val = newVal; this.label = newLabel; this.open = false; let hiddenInput = this.$refs.hiddenInput; if (hiddenInput) { hiddenInput.value = newVal; hiddenInput.dispatchEvent(new Event('change', { bubbles: true })); } @if($submit) this.$nextTick(() => { let form = this.$el.closest('form'); if (form) form.dispatchEvent(new CustomEvent('live-filter-change', { bubbles: true })); }); @endif } }"
    x-init="$watch('open', (isOpen) => { if (isOpen && window.lucide) window.lucide.createIcons(); })"
    @click.outside="open = false"
    @keydown.escape.window="open = false"
    class="relative"
>
    <input x-ref="hiddenInput" type="hidden" name="{{ $name }}" :value="val" value="{{ $value }}">

    <button
        type="button"
        @click="open = !open"
        :class="open ? 'border-indigo-400 bg-white shadow-sm ring-2 ring-indigo-500/15' : (val ? 'border-indigo-200 bg-indigo-50/60 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-300 hover:shadow-sm')"
        class="flex cursor-pointer items-center gap-1.5 rounded-lg border py-1.5 pl-2 pr-2.5 text-[13px] font-medium transition"
    >
        <span
            :class="val ? 'bg-indigo-600 text-white' : 'bg-slate-100 text-slate-500'"
            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md transition"
        >
            <i data-lucide="{{ $icon }}" class="h-3 w-3"></i>
        </span>
        <span
            x-text="label"
            :class="val ? 'text-slate-800 font-semibold' : 'text-slate-500 font-medium'"
            class="max-w-[8rem] truncate text-left"
        ></span>
        <span x-show="val" x-cloak class="h-1.5 w-1.5 shrink-0 rounded-full bg-indigo-500"></span>
        <i
            data-lucide="chevron-down"
            :class="open && 'rotate-180'"
            class="ml-0.5 h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform duration-200"
        ></i>
    </button>

    <!-- Dropdown: solid, panel menengah, isi lega dan simetris ke tengah -->
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1 scale-[0.98]"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-1 scale-[0.98]"
        style="display: none; translate: -50% 0;"
        class="absolute left-1/2 top-full z-50 mt-2 w-60 origin-top rounded-xl border border-slate-200 bg-white px-8 py-5 text-center shadow-xl shadow-slate-900/10"
    >
        <p class="pb-3 font-semibold uppercase tracking-[0.14em] text-slate-400" style="font-size: 10px;">{{ $placeholder }}</p>

        <div class="mx-auto w-full max-w-[176px] space-y-1.5">
            <button
                type="button"
                @click="select('', '{{ $sq($placeholder) }}')"
                :class="!val ? 'bg-indigo-50 text-indigo-700 font-semibold ring-1 ring-inset ring-indigo-100' : 'text-slate-600 hover:bg-slate-50 font-medium'"
                class="relative flex w-full cursor-pointer items-center justify-center gap-2 rounded-md px-8 py-2 text-center text-[13px] transition"
            >
                <span
                    :class="!val ? 'bg-indigo-100 text-indigo-600' : 'bg-slate-100 text-slate-400'"
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md"
                >
                    <i data-lucide="layers" class="h-3.5 w-3.5"></i>
                </span>
                <span class="truncate text-center">{{ $placeholder }}</span>
                <i x-show="!val" data-lucide="check" class="absolute right-2 h-3.5 w-3.5 shrink-0 text-indigo-600"></i>
            </button>

            <div class="mx-3 my-1 h-px bg-slate-100"></div>

            @foreach($options as $opt)
                @php $optVal = (string) ($opt['value'] ?? ''); @endphp
                <button
                    type="button"
                    @click="select('{{ $sq($optVal) }}', '{{ $sq($opt['label']) }}')"
                    :class="val === '{{ $sq($optVal) }}' ? 'bg-indigo-50 text-indigo-700 font-semibold ring-1 ring-inset ring-indigo-100' : 'text-slate-600 hover:bg-slate-50 font-medium'"
                    class="relative flex w-full cursor-pointer items-center justify-center gap-2 rounded-md px-8 py-2 text-center text-[13px] transition"
                >
                    <span
                        :class="val === '{{ $sq($optVal) }}' ? 'bg-indigo-100 text-indigo-600' : '{{ $opt['iconClass'] ?? 'bg-slate-100 text-slate-400' }}'"
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md"
                    >
                        <i data-lucide="{{ $opt['icon'] ?? 'circle-dot' }}" class="h-3.5 w-3.5"></i>
                    </span>
                    <span class="truncate text-center">{{ $opt['label'] }}</span>
                    <i x-show="val === '{{ $sq($optVal) }}'" data-lucide="check" class="absolute right-2 h-3.5 w-3.5 shrink-0 text-indigo-600"></i>
                </button>
            @endforeach
        </div>
    </div>
</div>
