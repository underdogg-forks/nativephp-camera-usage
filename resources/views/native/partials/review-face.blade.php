<native:pressable @press="editFace({{ $faceIdx }})" class="w-24 h-24 p-1 bg-gray-800 rounded">
    <native:column class="w-full h-full gap-1">
        @for($row = 0; $row < 3; $row++)
            <native:row class="w-full flex-1 gap-1">
                @for($col = 0; $col < 3; $col++)
                    @php 
                        $idx = $row * 3 + $col;
                        $colorCode = $faceData[$idx] ?? '?';
                        $bgClass = $colorMap[$colorCode] ?? 'bg-gray-500';
                    @endphp
                    <native:column class="flex-1 h-full rounded-sm {{ $bgClass }}"></native:column>
                @endfor
            </native:row>
        @endfor
    </native:column>
</native:pressable>
