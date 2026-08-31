<native:column class="w-full h-full bg-gray-900">

    @if($error)
        {{-- Error State --}}
        <native:column class="flex-1 items-center justify-center p-6 gap-4">
            <native:text class="text-5xl">⚠️</native:text>
            <native:text class="text-red-400 text-lg font-bold text-center">{{ $error }}</native:text>
            <native:text class="text-gray-400 text-sm text-center">Go to the Review tab to fix the colors, then come back here.</native:text>
        </native:column>

    @elseif(empty($steps))
        {{-- No steps state --}}
        <native:column class="flex-1 items-center justify-center p-6">
            <native:text class="text-gray-400 text-lg text-center">No solution found. Please re-scan your cube.</native:text>
        </native:column>

    @else
        {{-- Solution Found --}}

        {{-- Header summary --}}
        <native:column class="w-full bg-gray-800 p-4 gap-1">
            <native:text class="text-white text-xl font-bold">✅ Solution Found!</native:text>
            <native:text class="text-green-400 text-sm">{{ count($steps) }} moves needed to solve your cube.</native:text>
            <native:text class="text-gray-400 text-xs mt-1">Follow each move below in order. Standard notation: U=Top, D=Bottom, R=Right, L=Left, F=Front, B=Back. ' means counter-clockwise, 2 means twice.</native:text>
        </native:column>

        {{-- Move list --}}
        <native:scroll-view class="flex-1 w-full p-4">
            @foreach($steps as $index => $move)
                @php
                    $stepNum = $index + 1;
                    $face = $move[0] ?? '';
                    $modifier = substr($move, 1) ?? '';
                    $faceNames = [
                        'U' => 'Top face',
                        'D' => 'Bottom face',
                        'R' => 'Right face',
                        'L' => 'Left face',
                        'F' => 'Front face',
                        'B' => 'Back face',
                    ];
                    $faceName = $faceNames[$face] ?? $face;
                    $direction = match($modifier) {
                        "'" => 'counter-clockwise',
                        '2' => 'twice (180°)',
                        default => 'clockwise',
                    };
                    $bgColor = $index % 2 === 0 ? 'bg-gray-800' : 'bg-gray-750';
                @endphp
                <native:row class="w-full p-3 mb-2 rounded-xl bg-gray-800 items-center gap-3">
                    <native:column class="w-10 h-10 rounded-full bg-blue-600 items-center justify-center">
                        <native:text class="text-white font-bold text-sm">{{ $stepNum }}</native:text>
                    </native:column>
                    <native:column class="flex-1 gap-1">
                        <native:text class="text-white text-xl font-bold">{{ $move }}</native:text>
                        <native:text class="text-gray-400 text-sm">{{ $faceName }} — {{ $direction }}</native:text>
                    </native:column>
                </native:row>
            @endforeach

            {{-- Solved message at the bottom --}}
            <native:column class="w-full p-4 mt-2 items-center">
                <native:text class="text-green-400 text-lg font-bold">🎉 Done! Your cube is solved!</native:text>
            </native:column>
        </native:scroll-view>

    @endif

</native:column>
