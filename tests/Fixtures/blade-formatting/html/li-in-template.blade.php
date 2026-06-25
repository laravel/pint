<div>
<template x-for="thread in threads">
<li class="cursor-default select-none px-4 py-2 hover:bg-lio-100" :id="`option-${thread.id}`" role="option" tabindex="-1">
<a :href="'/forum/'+thread.slug" class="flex flex-col">
<span x-html="thread.value"></span>
</a>
</li>
</template>
</div>
