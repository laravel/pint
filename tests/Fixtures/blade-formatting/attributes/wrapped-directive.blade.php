<div @class([
    'space-y-2 rounded-md border border-slate-200/70 bg-slate-50/70 px-3 py-2.5 dark:border-slate-800/40 dark:bg-[#0b1324]/80' => $question->answer && ! $question->isSharedUpdate(),
    'space-y-1' => ! $question->answer || $question->isSharedUpdate(),
])>
    <div class="flex items-start {{ $question->isSharedUpdate() ? 'justify-end' : 'justify-between gap-3' }}">
        Body
    </div>
</div>
