<native:column class="w-full h-full bg-gray-900">

    @if($error)
        {{-- Error State --}}
        <native:column class="flex-1 items-center justify-center p-6 gap-4">
            <native:text class="text-5xl">⚠️</native:text>
            <native:text class="text-red-400 text-lg font-bold text-center">{{ $error }}</native:text>
            <native:text class="text-gray-400 text-sm text-center">Go to the Review tab to fix the colors, then come back here.</native:text>
        </native:column>

    @else
        <native:webview
            php
            javascript
            src="/solve-3d?solution={{ urlencode($solution ?? '') }}"
            class="w-full h-full"
        />
    @endif

</native:column>
