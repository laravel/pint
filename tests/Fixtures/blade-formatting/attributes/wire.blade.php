<div>
   <input wire:model.live.debounce.500ms="search" wire:keydown.enter="submit" type="text" />
        <button wire:click="save" wire:loading.attr="disabled" wire:target="save">Save</button>
      <livewire:questions.show :question-id="$comment->id" :inThread='true' :wire:key="$comment->id" />
    <form wire:submit.prevent="store">
            <div wire:key="row-{{ $row->id }}" wire:loading.class="opacity-50">
        {{    $row->name    }}
            </div>
    </form>
   <span wire:poll.5s>{{   $count   }}</span>
</div>
