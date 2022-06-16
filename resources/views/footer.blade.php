<div class="mb-1 mt-2 mx-2">
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
                    $fixableErrors = $issues->reject->isError();
                    $nonFixableErrors = $issues->filter->isError();
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
                        {{ $total }} {{ str('file')->plural($total) }}
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

    @foreach ($issues as $issue)
        <div class="flex space-x-1">
            <span>
                <span class="text-red font-bold">
                    {{ $issue->symbol() }}
                </span>
                <span class="ml-1">
                    {{ $issue->file() }}
                </span>
            </span>
            <span class="flex-1 truncate text-gray text-right">
                {{ $issue->description($testing) }}
            </span>
        </div>
    @endforeach
</div>
