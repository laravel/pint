<div class="mx-2 mt-2">
    <div class="flex space-x-1">
        <span class="content-repeat-[─] text-gray flex-1"></span>

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

                @if ($issues->count() == 0)
                    <span class="bg-green text-gray px-2 font-bold uppercase">PASS</span>
                @elseif ($nonFixableErrors->count() == 0 && ! $testing)
                    <span class="bg-green text-gray px-2 font-bold uppercase">FIXED</span>
                @else
                    <span class="bg-red px-2 font-bold uppercase text-white">FAIL</span>
                @endif

                <span class="content-repeat-[.] text-gray flex-1"></span>
                <span>
                    <span>
                        {{ $totalFiles }}
                        {{ str('file')->plural($totalFiles) }}
                    </span>

                    @if ($nonFixableErrors->isNotEmpty())
                        <span>
                            , {{ $nonFixableErrors->count() }}
                            {{ str('error')->plural($nonFixableErrors) }}
                        </span>
                    @endif

                    @if ($fixableErrors->isNotEmpty())
                        <span>
                            @if ($testing)
                                , {{ $fixableErrors->count() }} style
                                {{ str('issue')->plural($fixableErrors) }}
                            @else
                                , {{ $fixableErrors->count() }} style
                                {{ str('issue')->plural($fixableErrors) }}
                                fixed
                            @endif
                        </span>
                    @endif
                </span>
            </div>
        </span>
    </div>
</div>
