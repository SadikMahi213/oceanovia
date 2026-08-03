@props(['model', 'id', 'field', 'value', 'type' => 'text', 'options' => [], 'placeholder' => ''])

<div
    x-data="{
        editing: false,
        model: '{{ $model }}',
        recordId: {{ $id }},
        field: '{{ $field }}',
        value: {{ Js::from($value) }},
        tempValue: {{ Js::from($value) }},
        saving: false,
        startEdit() { this.tempValue = this.value; this.editing = true; this.$nextTick(() => { const el = this.$refs.input; if (el) el.focus(); }); },
        cancelEdit() { this.editing = false; this.tempValue = this.value; },
        async saveEdit() {
            this.saving = true;
            try {
                const resp = await fetch('{{ route('admin.inline.save') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.content },
                    body: JSON.stringify({ model: this.model, id: this.recordId, field: this.field, value: this.tempValue }),
                });
                const data = await resp.json();
                if (data.success) { this.value = data.value; this.editing = false; }
                else { alert(data.error || 'Save failed.'); }
            } catch (e) { alert('Network error.'); }
            finally { this.saving = false; }
        },
        handleKeydown(e) {
            if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); this.saveEdit(); }
            if (e.key === 'Escape') { this.cancelEdit(); }
        }
    }"
    class="inline-edit-wrapper"
>
    {{-- Display mode --}}
    <template x-if="!editing">
        <button @click="startEdit" type="button"
            class="inline-flex items-center gap-1 px-1.5 py-0.5 -mx-1.5 rounded hover:bg-market-50 dark:hover:bg-market-900/20 border border-transparent hover:border-market-200 dark:hover:border-market-700 transition-all cursor-text group text-left w-full"
            :title="'Click to edit ' + field">
            <span x-text="value ?? '—'" class="text-sm {{ $attributes->get('class', 'text-gray-900 dark:text-white') }}"></span>
            <svg class="w-3 h-3 text-gray-300 dark:text-gray-600 group-hover:text-market-400 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
        </button>
    </template>

    {{-- Edit mode --}}
    <template x-if="editing">
        <div class="flex items-center gap-1">
            @if($type === 'select')
                <select x-model="tempValue" x-ref="input" @keydown="handleKeydown"
                    class="w-full text-sm rounded-md border-market-300 dark:border-market-600 bg-white dark:bg-gray-800 shadow-sm focus:border-market-500 focus:ring-market-500">
                    <option value="">—</option>
                    @foreach($options as $optVal => $optLabel)
                        <option value="{{ $optVal }}">{{ $optLabel }}</option>
                    @endforeach
                </select>
            @elseif($type === 'number')
                <input type="number" step="any" x-model="tempValue" x-ref="input" @keydown="handleKeydown"
                    class="w-full text-sm rounded-md border-market-300 dark:border-market-600 bg-white dark:bg-gray-800 shadow-sm focus:border-market-500 focus:ring-market-500"
                    placeholder="{{ $placeholder }}">
            @else
                <input type="text" x-model="tempValue" x-ref="input" @keydown="handleKeydown"
                    class="w-full text-sm rounded-md border-market-300 dark:border-market-600 bg-white dark:bg-gray-800 shadow-sm focus:border-market-500 focus:ring-market-500"
                    placeholder="{{ $placeholder }}">
            @endif
            <button @click="saveEdit" type="button" :disabled="saving"
                class="p-1 rounded hover:bg-green-50 dark:hover:bg-green-900/20 text-green-600 dark:text-green-400 shrink-0"
                title="Save">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </button>
            <button @click="cancelEdit" type="button"
                class="p-1 rounded hover:bg-red-50 dark:hover:bg-red-900/20 text-red-500 shrink-0"
                title="Cancel">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div x-show="saving" class="shrink-0">
                <svg class="animate-spin w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            </div>
        </div>
    </template>
</div>
