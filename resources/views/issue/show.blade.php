<div class="mx-2 flex space-x-1">
    <span>
        <span class="text-red font-bold">
            {{ $issue->symbol() }}
        </span>
        <span class="ml-1">
            {{ $issue->file() }}
        </span>
    </span>
    <span class="text-gray {{ $isVerbose ? '' : 'truncate' }} flex-1 text-right">
        {{ $issue->description($testing) }}
    </span>
</div>
