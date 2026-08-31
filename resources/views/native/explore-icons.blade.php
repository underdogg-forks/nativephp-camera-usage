{{-- Icon catalog browser. The grid is a <virtual-list>: native renders
     $rowCount logical slots and fires setVirtualWindow(from, to) as the
     visible range moves; only rows inside [$from..$to] are materialized,
     each by rendering the `item` view once per index. The filtered
     catalog itself is shared as $iconCatalog (see ExploreIcons::render)
     because item views receive only ['index']. --}}
<column class="flex-1 w-full bg-theme-background">
    <text class="text-lg font-semibold text-theme-on-background px-5 pt-5">{{ $catalog['heading'] }} — {{ $total }} icons</text>

    @if ($total === 0)
        <text class="text-sm text-theme-on-surface-variant px-5 pt-3">No icons match "{{ $query }}"</text>
    @else
        <virtual-list
            class="w-full flex-1 px-5 py-3"
            item="native.explore-icons-row"
            :count="$rowCount"
            :from="$from"
            :to="$to"
            :estimated-row-height="96"
            :overscan="40"
            on-window-change="setVirtualWindow"
        />
    @endif

    {{-- Detail sheet — always in the tree, visibility driven by the tapped case. --}}
    <bottom-sheet :visible="$selectedCase !== null" detents="small" @dismiss="closeIconSheet">
        @if ($selectedCase !== null)
            <column class="w-full gap-2.5 p-6 items-center justify-center">
                <icon :ios="$catalog['ios'] ? $selectedCase : null"
                      :android="$catalog['ios'] ? null : $selectedCase"
                      :size="112" class="text-theme-on-surface"/>
                <text class="text-base font-semibold text-theme-on-surface text-center">{{ $enumShort }}::{{ $selectedCase->name }}</text>
                <text class="text-sm text-theme-on-surface-variant text-center">{{ $selectedCase->value }}</text>
            </column>
        @endif
    </bottom-sheet>
</column>
