<div>
    <button x-show="! recovery" x-on:click="open = ! open">A</button>
    <span x-text="!isLiked" :title="!busy ? a : b"></span>
    <p class="!mt-0 hover:!underline">important</p>
    <input :class="ok != bad" x-data="{ n: a !== b }" />
    <i x-show="!!flag"></i>
    <em x-on:click="alert('done!')" x-text="'!literal'"></em>
    <a @click="go" wire:click="save">x</a>
</div>
