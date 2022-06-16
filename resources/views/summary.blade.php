<div class="my-1 mx-2">
    <div></div>

    @if ($isVerbose)
        @foreach ($changes as $change)
            <div class="flex space-x-1">
                <span>{{ $change->file() }}</span>
                <span class="flex-1 content-repeat-[.] text-gray"></span>
                <span class="text-gray">{{ $change->issues() }} {{ $change->issues() > 1 ? 'issues' : 'issue' }}{{ $isDryRun ? '' : ' fixed' }}</span>
            </div>
        @endforeach
    @endif

    <hr class="text-gray">

    <div>
        <span>
            @if($isDryRun && count($changes) > 0)
                <div class="px-2 bg-yellow text-gray uppercase font-bold">wait</div>
                <em class="ml-1">
                    {{ $total - count($changes) }} files are respecting the <span class="font-bold">{{ $preset }}</span> coding style.
                    Yet, <span class="text-yellow font-bold">{{ count($changes) }} {{ count($changes) > 1 ? 'files' : 'file' }}</span> have issues.
                </em>
            @else
                <div class="px-2 bg-green text-gray uppercase font-bold">OK</div>
                <em class="ml-1">
                    {{ $total }} files are respecting the <span class="font-bold">{{ $preset }}</span> coding style.
                </em>
            @endif
        </span>
    </div>
</div>
