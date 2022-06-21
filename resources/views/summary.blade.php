<div class="mt-2 mx-2">
    <div class="flex space-x-1">
        <span class="flex-1 content-repeat-[─] text-gray">

        </span>

        <span class="text-gray">
            {{ $preset }}
        </span>
    </div>

    <div>
        <span>
            <div class="flex space-x-1">

                @php
                    $fixableErrors = $issues->filter->fixable();
                    $nonFixableErrors = $issues->reject->fixable();
                @endphp

                @if($issues->count() == 0)
                <span class="px-2 bg-green text-gray uppercase font-bold">
                    PASS
                </span>
                @elseif($nonFixableErrors->count() == 0 && ! $testing)
                <span class="px-2 bg-green text-gray uppercase font-bold">
                    FIXED
                </span>
                @else
                <span class="px-2 bg-red text-white uppercase font-bold">
                    FAIL
                </span>
                @endif

                <span class="flex-1 content-repeat-[.] text-gray"></span>
                <span>
                    <span>
                        {{ $totalFiles }} {{ str('file')->plural($totalFiles) }}
                    </span>

                    @if ($nonFixableErrors->isNotEmpty())
                    <span>
                        , {{ $nonFixableErrors->count() }} {{ str('error')->plural($nonFixableErrors) }}
                    </span>
                    @endif

                    @if ($fixableErrors->isNotEmpty())
                    <span>
                        @if ($testing)
                        , {{ $fixableErrors->count() }} style {{ str('issue')->plural($fixableErrors) }}
                        @else
                        , {{ $fixableErrors->count() }} style {{ str('issue')->plural($fixableErrors) }} fixed
                        @endif
                    </span>
                    @endif
                </span>
            </div>
        </span>
    </div>
</div>
