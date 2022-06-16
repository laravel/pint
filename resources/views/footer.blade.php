<div class="my-2 mx-2">
    <div class="flex space-x-1">
        <span class="flex-1 content-repeat-[─] text-gray">

        </span>

        <span class="italic text-gray">
            {{ $preset }} Coding Style
        </span>
    </div>

    <div>
        <span>
            @if($isDryRun && count($changes) > 0)
                <div class="flex space-x-1">
                    <span class="px-2 bg-red text-white uppercase font-bold">
                        FAIL
                    </span>
                    <span class="flex-1 content-repeat-[.] text-gray"></span>
                    <span>
                       {{ $total }} files, {{ count($changes) }} file(s) failed
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
                            {{ $total }} files
                        </span>
                        <span>
                        @if (count($changes))
                            , {{ count($changes) }} file(s) fixed
                        @endif
                        </span>
                    </span>
                </div>
            @endif
        </span>
    </div>

    @if ($isVerbose)
        @foreach ($changes as $change)
            <div>
                <span class="text-red font-bold">
                    ⨯
                </span>
                <span class="ml-1">
                    {{ $change->file() }}
                </span>
            </div>
        @endforeach
    @endif
</div>
