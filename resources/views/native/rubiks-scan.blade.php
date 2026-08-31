<native:column class="w-full h-full bg-gray-900">
@if($showGuide)
    <native:column class="w-full h-full bg-black z-50">
        <native:row class="w-full bg-gray-900 justify-between items-center p-4 pt-8">
            <native:text class="text-white text-xl font-bold">Scanning Guide</native:text>
            <native:pressable @press="closeGuide" class="bg-red-600 px-4 py-2 rounded">
                <native:text class="text-white font-bold">✕ Close</native:text>
            </native:pressable>
        </native:row>
        
        <native:column class="flex-1 items-center justify-center p-6 gap-6">
            {{-- header omitted - title is now inside the instruction block --}}
            
            @php
                $guideInstructions = [
                    1 => [
                        'title'   => 'Scan White Face (Top)',
                        'detail'  => "Lay the cube FLAT on a table — Yellow face DOWN, White face UP, Green center FACING YOU. Point your camera straight down at the White face and tap White (Top) on the grid.",
                        'image'   => 'images/guide/step1_white.jpg',
                        'emoji'   => '⬜',
                    ],
                    2 => [
                        'title'   => 'Scan Green Face (Front)',
                        'detail'  => "Stand the cube upright. Hold it with White on top. The Green center should face you. Tap Front on the grid.",
                        'image'   => 'images/guide/step2_green.jpg',
                        'emoji'   => '🟩',
                    ],
                    3 => [
                        'title'   => 'Scan Red Face (Right)',
                        'detail'  => "Keeping White on top, rotate the cube 90° to the LEFT so the Red center now faces you. Tap Right on the grid.",
                        'image'   => 'images/guide/step3_red.jpg',
                        'emoji'   => '🟥',
                    ],
                    4 => [
                        'title'   => 'Scan Blue Face (Back)',
                        'detail'  => "Keeping White on top, rotate the cube another 90° LEFT so the Blue center now faces you. Tap Back on the grid.",
                        'image'   => 'images/guide/step4_blue.jpg',
                        'emoji'   => '🟦',
                    ],
                    5 => [
                        'title'   => 'Scan Orange Face (Left)',
                        'detail'  => "Keeping White on top, rotate the cube another 90° LEFT so the Orange center now faces you. Tap Left on the grid.",
                        'image'   => 'images/guide/step5_orange.jpg',
                        'emoji'   => '🟧',
                    ],
                    6 => [
                        'title'   => 'Scan Yellow Face (Bottom)',
                        'detail'  => "From the Orange step, lay the cube flat — White face DOWN, Green facing AWAY from you. The Yellow face is now on top. Point your camera straight down at Yellow and tap Bottom on the grid.",
                        'image'   => 'images/guide/step6_yellow.jpg',
                        'emoji'   => '🟨',
                    ],
                ];
                $step = $guideStep ?? 1;
                $g = $guideInstructions[$step];
            @endphp

            {{-- Step title --}}
            <native:text class="text-white text-2xl font-bold text-center">{{ $g['emoji'] }} {{ $g['title'] }}</native:text>

            {{-- Instruction --}}
            <native:column class="bg-gray-800 rounded-2xl p-8 mx-4 my-8 justify-center">
                <native:text class="text-gray-200 text-lg text-center leading-7">{{ $g['detail'] }}</native:text>
            </native:column>

            {{-- Step counter --}}
            <native:text class="text-gray-500 text-xs text-center">Step {{ $step }} of 6</native:text>

            {{-- Prev / Next --}}
            <native:row class="w-full justify-center gap-4">
                <native:pressable @press="prevGuideStep" class="bg-gray-700 px-6 py-3 rounded-lg">
                    <native:text class="text-white font-bold">← Prev</native:text>
                </native:pressable>
                <native:pressable @press="nextGuideStep" class="{{ $step === 6 ? 'bg-green-600' : 'bg-blue-600' }} px-6 py-3 rounded-lg">
                    <native:text class="text-white font-bold">{{ $step === 6 ? '✓ Done' : 'Next →' }}</native:text>
                </native:pressable>
            </native:row>
        </native:column>
    </native:column>
@elseif($isAligning && $pendingPhotoPath !== null)
    <!-- RESULTS SCREEN (OpenCV Feedback) -->
    <native:column class="w-full h-full bg-black">
        <native:text class="text-white text-center font-bold text-lg p-4 pt-8">Scan Results</native:text>
        <native:text class="text-gray-300 text-center text-xs px-4 pb-4">Check if the circles accurately align with the cube's stickers. If not, retake the photo.</native:text>
        
        <native:image src="{{ $pendingPhotoPath }}" class="w-full flex-1 object-contain" />
        
        <native:row class="w-full">
            <native:pressable @press="cancelAlignment" class="bg-red-800 p-4 flex-1 items-center justify-center">
                <native:text class="text-white font-bold text-sm">Retake</native:text>
            </native:pressable>
            <native:pressable @press="confirmAlignment" class="bg-green-600 p-4 flex-1 items-center justify-center">
                <native:text class="text-white font-bold text-sm">Looks Good</native:text>
            </native:pressable>
        </native:row>
    </native:column>
@else
    <!-- SCAN SCREEN -->
    <native:column class="w-full h-full items-center justify-start bg-gray-900 pt-8 pb-10">
        <native:row class="w-full justify-between items-center px-6 mb-4">
            <native:text class="text-2xl font-bold text-white">Scan Cube Faces</native:text>
            <native:pressable @press="openGuide" class="bg-blue-600 px-3 py-2 rounded shadow-lg">
                <native:text class="text-white font-bold text-xs">How to Scan?</native:text>
            </native:pressable>
        </native:row>
        
        <native:text class="text-sm text-gray-300 px-6 text-center mb-4">Tap a face below to scan it. Start with White (lay cube flat). Tap "How to Scan?" for step-by-step help.</native:text>

        <!-- Hidden tailwind dynamic classes to ensure JIT compiler includes them -->
        <native:column class="hidden bg-white bg-yellow-400 bg-green-500 bg-blue-600 bg-orange-500 bg-red-600 bg-gray-500 border-black"></native:column>

        @php
            $colorsMap = [
                'U' => 'bg-white', 'D' => 'bg-yellow-400', 'F' => 'bg-green-500', 
                'B' => 'bg-blue-600', 'L' => 'bg-orange-500', 'R' => 'bg-red-600',
            ];
        @endphp
        
        @if($errorMessage)
            <native:row class="w-full bg-red-900 p-2 rounded justify-center items-center">
                <native:text class="text-white text-xs text-center font-bold">{{ $errorMessage }}</native:text>
            </native:row>
        @endif

        <!-- Original Grid -->
        <native:column class="items-center gap-1">
            <!-- Row 1: Top Face -->
            <native:row class="w-full justify-center gap-1">
                <native:column class="w-20 h-20 bg-transparent"></native:column>
                
                <native:pressable @press="takePhoto(0)" class="w-20 h-20 border-2 {{ isset($scannedFaces[0]) ? 'border-black bg-black' : 'border-dashed border-gray-500 bg-gray-800' }} items-center justify-center rounded">
                    @if(isset($scannedFaces[0]))
                        <native:column class="w-[76px] h-[76px] overflow-hidden">
                            @for($r = 0; $r < 3; $r++)
                                <native:row class="w-full flex-1">
                                    @for($c = 0; $c < 3; $c++)
                                        <native:column class="flex-1 h-full border border-black {{ $colorsMap[$scannedFaces[0][$r*3+$c]] ?? 'bg-gray-500' }}"></native:column>
                                    @endfor
                                </native:row>
                            @endfor
                        </native:column>
                    @else
                        <native:text class="text-white text-xs font-bold">⬜ White</native:text>
                        <native:text class="text-gray-400 text-[10px]">Scan FIRST</native:text>
                    @endif
                </native:pressable>
                
                <native:column class="w-20 h-20 bg-transparent"></native:column>
            </native:row>

            <!-- Row 2: Left, Front, Right -->
            <native:row class="w-full justify-center gap-1">
                @foreach([1 => ['Left', '🟧 Orange'], 2 => ['Front', '🟩 Green'], 3 => ['Right', '🟥 Red']] as $idx => $label)
                    <native:pressable @press="takePhoto({{ $idx }})" class="w-20 h-20 border-2 {{ isset($scannedFaces[$idx]) ? 'border-black bg-black' : 'border-dashed border-gray-500 bg-gray-800' }} items-center justify-center rounded">
                        @if(isset($scannedFaces[$idx]))
                            <native:column class="w-[76px] h-[76px] overflow-hidden">
                                @for($r = 0; $r < 3; $r++)
                                    <native:row class="w-full flex-1">
                                        @for($c = 0; $c < 3; $c++)
                                            <native:column class="flex-1 h-full border border-black {{ $colorsMap[$scannedFaces[$idx][$r*3+$c]] ?? 'bg-gray-500' }}"></native:column>
                                        @endfor
                                    </native:row>
                                @endfor
                            </native:column>
                        @else
                            <native:text class="text-white text-xs font-bold">{{ $label[0] }}</native:text>
                            <native:text class="text-gray-400 text-[10px]">{{ $label[1] }}</native:text>
                        @endif
                    </native:pressable>
                @endforeach
            </native:row>

            <!-- Row 3: Bottom Face -->
            <native:row class="w-full justify-center gap-1">
                <native:column class="w-20 h-20 bg-transparent"></native:column>
                
                <native:pressable @press="takePhoto(4)" class="w-20 h-20 border-2 {{ isset($scannedFaces[4]) ? 'border-black bg-black' : 'border-dashed border-gray-500 bg-gray-800' }} items-center justify-center rounded">
                    @if(isset($scannedFaces[4]))
                        <native:column class="w-[76px] h-[76px] overflow-hidden">
                            @for($r = 0; $r < 3; $r++)
                                <native:row class="w-full flex-1">
                                    @for($c = 0; $c < 3; $c++)
                                        <native:column class="flex-1 h-full border border-black {{ $colorsMap[$scannedFaces[4][$r*3+$c]] ?? 'bg-gray-500' }}"></native:column>
                                    @endfor
                                </native:row>
                            @endfor
                        </native:column>
                    @else
                        <native:text class="text-white text-xs font-bold">🟨 Yellow</native:text>
                        <native:text class="text-gray-400 text-[10px]">Scan LAST</native:text>
                    @endif
                </native:pressable>
                
                <native:column class="w-20 h-20 bg-transparent"></native:column>
            </native:row>

            <!-- Row 4: Back Face -->
            <native:row class="w-full justify-center gap-1">
                <native:column class="w-20 h-20 bg-transparent"></native:column>
                
                <native:pressable @press="takePhoto(5)" class="w-20 h-20 border-2 {{ isset($scannedFaces[5]) ? 'border-black bg-black' : 'border-dashed border-gray-500 bg-gray-800' }} items-center justify-center rounded">
                    @if(isset($scannedFaces[5]))
                        <native:column class="w-[76px] h-[76px] overflow-hidden">
                            @for($r = 0; $r < 3; $r++)
                                <native:row class="w-full flex-1">
                                    @for($c = 0; $c < 3; $c++)
                                        <native:column class="flex-1 h-full border border-black {{ $colorsMap[$scannedFaces[5][$r*3+$c]] ?? 'bg-gray-500' }}"></native:column>
                                    @endfor
                                </native:row>
                            @endfor
                        </native:column>
                    @else
                        <native:text class="text-white text-xs font-bold">🟦 Blue</native:text>
                        <native:text class="text-gray-400 text-[10px]">Back</native:text>
                    @endif
                </native:pressable>
                
                <native:column class="w-20 h-20 bg-transparent"></native:column>
            </native:row>
        </native:column>

        @if(count(array_filter($scannedFaces)) > 0)
        <native:pressable @press="resetScan" class="bg-red-600 px-6 py-3 rounded-xl mt-8">
            <native:text class="text-white font-bold text-lg">Reset All Scans</native:text>
        </native:pressable>
        @endif
    </native:column>
@endif
</native:column>
