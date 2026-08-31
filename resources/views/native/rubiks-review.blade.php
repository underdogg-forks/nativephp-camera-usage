<native:column class="w-full h-full bg-gray-900">
    <!-- Header -->
    <native:row class="w-full p-4 items-center justify-between bg-gray-800">
        <native:text class="text-xl font-bold text-white">Review & Edit</native:text>
    </native:row>

    @php
        $colorMap = [
            'U' => 'bg-white',
            'D' => 'bg-yellow-400',
            'F' => 'bg-green-500',
            'B' => 'bg-blue-600',
            'L' => 'bg-orange-500',
            'R' => 'bg-red-600',
            '?' => 'bg-gray-500'
        ];
        // Ensure all faces exist
        $safeFaces = [];
        for($i=0; $i<6; $i++) {
            $safeFaces[$i] = isset($faces[$i]) && is_array($faces[$i]) && count($faces[$i]) === 9 ? $faces[$i] : array_fill(0, 9, '?');
        }
    @endphp

    <!-- Hidden tailwind dynamic classes to ensure JIT compiler includes them -->
    <native:column class="hidden bg-white bg-yellow-400 bg-green-500 bg-blue-600 bg-orange-500 bg-red-600 bg-gray-500"></native:column>

    @if($editingFace === null)
        <!-- Cross Layout View -->
        <native:column class="flex-1 w-full items-center justify-center p-4">
            <native:text class="text-gray-400 text-sm mb-6 text-center">Tap any face to correct its colors if the camera got them wrong or upside down.</native:text>
            
            <native:column class="gap-1">
                <!-- Top Row: Top face -->
                <native:row class="w-full justify-center gap-1">
                    <native:column class="w-24"></native:column>
                    @include('native.partials.review-face', ['faceIdx' => 0, 'faceData' => $safeFaces[0], 'colorMap' => $colorMap])
                    <native:column class="w-24"></native:column>
                </native:row>

                <!-- Middle Row: Left, Front, Right -->
                <native:row class="w-full justify-center gap-1">
                    @include('native.partials.review-face', ['faceIdx' => 1, 'faceData' => $safeFaces[1], 'colorMap' => $colorMap])
                    @include('native.partials.review-face', ['faceIdx' => 2, 'faceData' => $safeFaces[2], 'colorMap' => $colorMap])
                    @include('native.partials.review-face', ['faceIdx' => 3, 'faceData' => $safeFaces[3], 'colorMap' => $colorMap])
                </native:row>

                <!-- Bottom Row: Bottom face -->
                <native:row class="w-full justify-center gap-1">
                    <native:column class="w-24"></native:column>
                    @include('native.partials.review-face', ['faceIdx' => 4, 'faceData' => $safeFaces[4], 'colorMap' => $colorMap])
                    <native:column class="w-24"></native:column>
                </native:row>

                <!-- Back Row: Back face -->
                <native:row class="w-full justify-center gap-1">
                    <native:column class="w-24"></native:column>
                    @include('native.partials.review-face', ['faceIdx' => 5, 'faceData' => $safeFaces[5], 'colorMap' => $colorMap])
                    <native:column class="w-24"></native:column>
                </native:row>
            </native:column>
        </native:column>
    @else
        <!-- Editing View (One Face) -->
        <native:column class="flex-1 w-full items-center justify-center p-4 bg-gray-900 absolute inset-0 z-50">
        <native:text class="text-white text-xl mb-4 font-bold">Edit Face</native:text>
        <native:text class="text-gray-400 text-sm mb-8 text-center">Tap a square to cycle its color.</native:text>
        
        <native:column class="bg-gray-800 p-2 rounded-lg gap-2 w-64 h-64">
            @for($row = 0; $row < 3; $row++)
            <native:row class="gap-2 flex-1">
                @for($col = 0; $col < 3; $col++)
                    @php 
                        $idx = $row * 3 + $col; 
                        $colorCode = $safeFaces[$editingFace][$idx] ?? '?';
                        $bgClass = $colorMap[$colorCode] ?? 'bg-gray-500';
                    @endphp
                    <native:pressable @press="cycleColor({{ $editingFace }}, {{ $idx }})" class="flex-1 rounded-md">
                        <native:column class="w-full h-full rounded-md {{ $bgClass }}"></native:column>
                    </native:pressable>
                @endfor
            </native:row>
            @endfor
        </native:column>

        <native:pressable @press="closeEdit" class="mt-8 bg-green-600 px-8 py-3 rounded-lg">
            <native:text class="text-white font-bold text-lg">Done</native:text>
        </native:pressable>
    </native:column>
    @endif
</native:column>
