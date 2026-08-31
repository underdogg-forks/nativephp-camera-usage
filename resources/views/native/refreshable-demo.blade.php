<refreshable @refresh="refresh" class="w-full h-full bg-theme-background">
    <column class="w-full p-4 gap-3">

        <text class="text-xs uppercase tracking-wider text-theme-on-surface-variant px-1">
            Refreshed {{ $refreshCount }}x — pull down ↓
        </text>

        @foreach ($cards as $card)
            <column :class="'w-full p-4 rounded-2xl bg-' . $card['color']">
                <text class="text-white text-base font-semibold">{{ $card['title'] }}</text>
                <text class="text-white text-xs">{{ $card['subtitle'] }}</text>
            </column>
        @endforeach

    </column>
</refreshable>
