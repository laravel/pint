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
            @if($issues->isNotEmpty() > 0)
                <div class="flex space-x-1">
                    <span class="px-2 bg-red text-white uppercase font-bold">
                        FAIL
                    </span>
                    <span class="flex-1 content-repeat-[.] text-gray"></span>
                    <span>
                        <span>
                            {{ $total }} {{ str('file')->plural($total) }}
                        </span>

                        @php
                        $nonFixableErrors = $issues->filter->isError();
                        @endphp

                        @if ($nonFixableErrors->isNotEmpty())
                        <span>
                            , {{ $nonFixableErrors->count() }} {{ str('error')->plural($nonFixableErrors) }}
                        </span>
                        @endif

                        @php
                            $fixableErrors = $issues->reject->isError();
                        @endphp

                        @if ($fixableErrors->isNotEmpty())
                        <span>
                            @if ($pretending)
                            , {{ $fixableErrors->count() }} style {{ str('issue')->plural($fixableErrors) }}
                            @else
                            , {{ $fixableErrors->count() }} style {{ str('issues')->plural($fixableErrors) }} fixed
                            @endif
                        </span>
                        @endif

                    </span>
                </div>
            @else
                <div class="flex space-x-1">
                    <span class="px-2 bg-green text-gray uppercase font-bold">
                        PASS
                    </span>
                    <span class="flex-1 content-repeat-[.] text-gray"></span>
                    <span>
                        <span>
                            {{ $total }} {{ str('file')->plural($total) }}
                        </span>
                        <span>
                        @if ($issues->isNotEmpty())
                            , {{ $issues->isNotEmpty() }} {{ str('file')->plural($total) }} fixed
                        @endif
                        </span>
                    </span>
                </div>
            @endif
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
                {{ $issue->description($pretending) }}
            </span>
        </div>
    @endforeach
</div>
