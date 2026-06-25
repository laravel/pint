<div class="mx-2 flex space-x-1">
    <span>
        <span class="text-red font-bold"> {{ $issue->symbol() }} </span>
        <span class="ml-1"> {{ $issue->file() }} </span>
    </span>
    <span class="flex-1 text-gray text-right {{ $isVerbose ? '' : 'truncate' }}">
        {{ $issue->description($testing) }}
    </span>
</div>
