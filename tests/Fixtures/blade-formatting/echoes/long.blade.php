<p>{{ __('This is a really long translation string that should never be exploded across multiple lines by the formatter no matter what') }}</p>
<p>{{ $user->profile->settings->preferences->notifications->email ? 'Enabled and active right now' : 'Disabled completely' }}</p>
<p>{{ collect($items)->map(fn ($i) => $i->name)->filter()->unique()->sort()->implode(', ') }}</p>
