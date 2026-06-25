<x-app-layout>
<x-slot:header>
<x-page-heading :title="$title" />
</x-slot:header>
<x-card>
<x-card.body>
<livewire:questions.show :questionId="$rootId" :in-thread="true" :key="'question-'.$rootId" />
<x-comments :question="$comment" wire:key="comment-{{ $comment->id }}" />
</x-card.body>
</x-card>
</x-app-layout>
