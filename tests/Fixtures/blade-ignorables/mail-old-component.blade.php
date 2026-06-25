@component('mail::message')
# Hello

Body paragraph one.

@component('mail::button', ['url' => $url])
Click Here
@endcomponent

Thanks
@endcomponent
