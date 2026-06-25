<div>
    <x-dynamic-component :component="$componentName" :user="$user" class="block" />
    <x-dynamic-component component="alert" type="error">
        Something went wrong
    </x-dynamic-component>
    <component :is="$tag">{{ $slot }}</component>
</div>
