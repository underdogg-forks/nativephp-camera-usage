<native:pressable @press="editFace({{ $faceIdx }})" class="w-24 h-24 bg-gray-800 rounded-md p-1 gap-0.5">
    @for($row = 0; $row < 3; $row++)
    <native:row class="flex-1 gap-0.5">
        @for($col = 0; $col < 3; $col++)
            @php $idx = $row * 3 + $col; @endphp
            <native:column class="flex-1 h-full rounded-sm {{ $colorMap[$faceData[$idx] ?? '?'] }}"></native:column>
        @endfor
    </native:row>
    @endfor
</native:pressable>
