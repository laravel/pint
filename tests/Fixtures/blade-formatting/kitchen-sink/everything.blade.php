{{-- everything.blade.php: a deliberately messy, exhaustive Blade document --}}
@php
use App\Models\User;
use App\Support\Money;
@endphp
@use('App\Enums\Status')
@inject('metrics', 'App\Services\Metrics')
<!DOCTYPE html>
<html lang="{{str_replace('_','-',app()->getLocale())}}" @if($rtl) dir="rtl" @endif>
<head>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{csrf_token()}}">
<title>@yield('title',   'Dashboard')  · {{config('app.name')}}</title>
<link rel="preconnect" href="https://fonts.bunny.net">
<style>
:root{--brand:{{$brand??'#4f46e5'}};--radius:{{$radius}}px;}
body{margin:0;font-family:{{$font}};}
@if($dense)
.row{padding:.25rem;}
@else
.row{padding:.75rem;}
@endif
@media(min-width:{{$bp}}px){.container{max-width:{{$max}};}}
.badge::after{content:'{{$suffix}}';}
</style>
@stack('head')
</head>
<body class="antialiased   bg-white   dark:bg-gray-900   text-gray-800" x-data="{open:false, tab:'home', count:0, dark:false}">
{{-- top navigation --}}
<header class="border-b">
<nav @class(['nav','nav--fixed'=>$fixed,'nav--transparent'=>!$fixed])>
<a href="{{route('home')}}" wire:navigate>{{__('Home')}}</a>
<a href="{{route('pricing')}}" @class(['link','on'=>$onPricing])>Pricing</a>
@auth
<span>Hello, {{auth()->user()->name}}</span>
<form method="POST" action="{{route('logout')}}">
@csrf
@method('DELETE')
<button type="submit" @disabled($processing)>Logout</button>
</form>
@else
<a href="{{route('login')}}">{{__('Log in')}}</a>
@guest
<a href="{{route('register')}}">Register</a>
@endguest
@endauth
<button type="button" @click="dark = !dark" aria-label="Toggle theme">🌓</button>
</nav>
</header>
<main class="container mx-auto px-4 py-8    space-y-6">
{{-- flash + validation errors --}}
@if(session('status'))
<div class="alert alert-success">{{session('status')}}</div>
@elseif(session('warning'))
<div class="alert alert-warning">{{session('warning')}}</div>
@endif
@error('email')
<p class="text-red-600">{{$message}}</p>
@enderror
@error('password')
<p class="text-red-600">{{$message}}</p>
@else
<p class="text-gray-400">Choose a strong password.</p>
@enderror
@if($errors->any())
<ul class="errors">
@foreach($errors->all() as $error)
<li>{{$error}}</li>
@endforeach
</ul>
@endif
{{-- switch over a status enum --}}
@switch($status)
@case(Status::Active)
<span class="badge badge-green">Active</span>
@break
@case(Status::Pending)
@case(Status::Review)
<span class="badge badge-yellow">Waiting</span>
@break
@case(Status::Archived)
<span class="badge badge-gray">Archived</span>
@break
@default
<span class="badge badge-gray">Unknown</span>
@endswitch
{{-- nested loops with forelse + loop variable --}}
@foreach($groups as $group)
@continue($group->hidden)
<section data-id="{{$group->id}}" @class(['group','group--active'=>$loop->first])>
<h2>{{$loop->iteration}}. {{$group->name}}</h2>
@forelse($group->items as $item)
<article @class(['item','item--first'=>$loop->first,'item--last'=>$loop->last]) wire:key="item-{{$item->id}}">
<h3>{{$item->title}}</h3>
@isset($item->author)
<p>By {{$item->author->name}}</p>
@endisset
@unless($item->published)
<span>Draft</span>
@endunless
<p>{{Str::limit($item->body,120)}}</p>
{!! $item->rendered_html !!}
<span>Price: {{Money::format($item->price)}}</span>
@if($item->onSale)
<del>{{$item->price}}</del> <ins>{{$item->sale_price}}</ins>
@elseif($item->preorder)
<em>Pre-order</em>
@else
<span>{{$item->price}}</span>
@endif
@if($item->tags->isNotEmpty())
<ul class="tags">
@foreach($item->tags as $tag)
<li @class(['tag','tag--primary'=>$tag->primary])>{{$tag->name}}</li>
@endforeach
</ul>
@endif
</article>
@empty
<p class="muted">Nothing here yet.</p>
@endforelse
</section>
@endforeach
{{-- while loop with break/continue --}}
@php $i=0; @endphp
@while($i<$limit)
@php $i++; @endphp
@continue($i%2===0)
@if($i>100) @break @endif
<span>{{$i}}</span>
@endwhile
{{-- classic for loop --}}
@for($page=1;$page<=$pages;$page++)
<a href="?page={{$page}}" @class(['page','page--current'=>$page===$current])>{{$page}}</a>
@endfor
{{-- components, slots, attributes --}}
<x-layout :title="$title" :user="auth()->user()" class="shadow">
<x-slot:header>
<h1>{{$heading}}</h1>
</x-slot:header>
<x-slot name="sidebar" :collapsed="$collapsed">
@foreach($links as $link)
<x-nav-link :href="$link['url']" :active="request()->is($link['pattern'])">{{$link['label']}}</x-nav-link>
@endforeach
</x-slot>
<x-alert type="info" dismissible :messages="$notices" @class(['mb-4'])>
Default slot content goes right here.
</x-alert>
<x-dynamic-component :component="$widget" :data="$payload" />
<x-card>
<x-slot:title>{{$card->title}}</x-slot:title>
{{$card->body}}
</x-card>
{{-- deeply nested conditionals inside components --}}
<x-panel>
@if($user->subscribed())
@if($user->onTrial())
<x-badge color="blue">Trial</x-badge>
@else
@if($user->onGracePeriod())
<x-badge color="amber">Grace period</x-badge>
@else
<x-badge color="green">Active</x-badge>
@endif
@endif
@else
<x-badge color="gray">Free</x-badge>
@endif
</x-panel>
{{-- forms with attribute directives --}}
<form method="POST" action="{{route('profile.update')}}" enctype="multipart/form-data">
@csrf
@method('PUT')
<input type="text" name="name" value="{{old('name',$user->name)}}" @required($strict) @readonly($locked)>
<input type="email" name="email" value="{{old('email',$user->email)}}" @class(['input','input--error'=>$errors->has('email')])>
<select name="country">
@foreach($countries as $code=>$label)
<option value="{{$code}}" @selected(old('country',$user->country)===$code)>{{$label}}</option>
@endforeach
</select>
<select name="roles[]" multiple>
@foreach($roles as $role)
<option value="{{$role->id}}" @selected(in_array($role->id,old('roles',$user->role_ids)))>{{$role->name}}</option>
@endforeach
</select>
<label><input type="checkbox" name="tos" @checked(old('tos'))> Accept terms</label>
<label><input type="radio" name="plan" value="pro" @checked(old('plan')==='pro')> Pro</label>
<textarea name="bio" @if($readonly) readonly @endif>{{old('bio',$user->bio)}}</textarea>
<button type="submit" @disabled($processing)>Save</button>
</form>
</x-layout>
{{-- tables --}}
<table class="w-full">
<thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th><th></th></tr></thead>
<tbody>
@forelse($users as $user)
<tr @class(['odd'=>$loop->odd,'even'=>$loop->even,'row--you'=>$user->is(auth()->user())])>
<td>{{$user->name}}</td>
<td>{{$user->email}}</td>
<td>@if($user->active) ✓ @else ✗ @endif</td>
<td>{{$user->created_at->diffForHumans()}}</td>
<td>
@can('update',$user)
<a href="{{route('users.edit',$user)}}">Edit</a>
@endcan
</td>
</tr>
@empty
<tr><td colspan="5">No users found.</td></tr>
@endforelse
</tbody>
<tfoot>
<tr><td colspan="5">Total: {{$users->total()}}</td></tr>
</tfoot>
</table>
{{$users->links()}}
{{-- a grid of cards built from a collection --}}
<div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
@foreach($products as $product)
<x-product-card :product="$product" wire:key="product-{{$product->id}}" @class(['featured'=>$product->featured])>
<x-slot:image>
<img src="{{$product->image_url}}" alt="{{$product->name}}" loading="lazy">
</x-slot:image>
<h3>{{$product->name}}</h3>
<p>{{$product->description}}</p>
@if($product->inStock())
<button wire:click="addToCart({{$product->id}})" @disabled($product->reserved)>Add to cart</button>
@else
<span class="text-red-500">Out of stock</span>
@endif
</x-product-card>
@endforeach
</div>
{{-- alpine tabs --}}
<div x-data="{active:'overview'}" class="tabs">
<nav>
<button @click="active='overview'" :class="{'is-active':active==='overview'}">Overview</button>
<button @click="active='activity'" :class="{'is-active':active==='activity'}">Activity</button>
<button @click="active='settings'" :class="{'is-active':active==='settings'}">Settings</button>
</nav>
<div x-show="active==='overview'" x-transition>{{$overview}}</div>
<div x-show="active==='activity'" x-cloak>
@foreach($activities as $activity)
<p><strong>{{$activity->actor}}</strong> {{$activity->description}} <time>{{$activity->created_at->diffForHumans()}}</time></p>
@endforeach
</div>
<div x-show="active==='settings'" x-cloak>{{$settingsPanel}}</div>
</div>
{{-- a modal built with alpine --}}
<div x-data="{show:false}">
<button @click="show=true">Open modal</button>
<template x-teleport="body">
<div x-show="show" @keydown.escape.window="show=false" class="modal">
<div class="modal__backdrop" @click="show=false"></div>
<div class="modal__panel" role="dialog" aria-modal="true">
<h2>{{$modalTitle}}</h2>
<p>{{$modalBody}}</p>
<button @click="show=false">Close</button>
</div>
</div>
</template>
</div>
{{-- includes and each --}}
@include('partials.footer',['year'=>date('Y')])
@includeIf('partials.debug')
@includeWhen($debug,'partials.debug-panel',['data'=>$debugData])
@includeUnless($production,'partials.dev-banner')
@includeFirst(['custom.hero','default.hero'])
@each('partials.comment',$comments,'comment','partials.no-comments')
{{-- push, prepend, once --}}
@push('scripts')
<script src="/js/app.js" defer></script>
@endpush
@pushOnce('styles')
<link rel="stylesheet" href="/css/widget.css">
@endPushOnce
@prepend('scripts')
<script src="/js/polyfills.js"></script>
@endprepend
@once
<script>window.__booted = true;</script>
@endonce
{{-- verbatim + escaped echoes --}}
@verbatim
<div id="app">{{ vueVariable }} and @{{ alsoVue }}</div>
@endverbatim
<p>Literal braces: @{{ notBlade }}</p>
{{-- fragments (Livewire) --}}
@fragment('list')
<ul>
@foreach($rows as $row)
<li>{{$row}}</li>
@endforeach
</ul>
@endfragment
{{-- long echo that should wrap --}}
<div>{{$user->subscription->active?'Subscribed and enjoying every single premium feature we offer to our valued customers':'Not subscribed to any plan at the moment, so please consider upgrading today'}}</div>
{{-- chained method echo --}}
<p>{{collect($metrics->all())->map(fn($m)=>$m->value)->filter(fn($v)=>$v>0)->sum()}}</p>
{{-- raw echo and escaped braces mixed --}}
<div data-json='@json($config)'>{!! $trustedHtml !!}</div>
{{-- definition list --}}
<dl>
@foreach($facts as $term=>$definition)
<dt>{{$term}}</dt>
<dd>{{$definition}}</dd>
@endforeach
</dl>
{{-- blockquote with nested echo --}}
<blockquote cite="{{$quote->source}}">
<p>{{$quote->text}}</p>
<footer>— {{$quote->author}}</footer>
</blockquote>
{{-- alpine + inline handlers --}}
<div x-show="open" @click.away="open=false" :class="{'active':tab==='home'}" x-transition>
<button @click="count++" :disabled="count>=10">Increment</button>
<span x-text="count"></span>
</div>
{{-- svg icons --}}
<svg viewBox="0 0 24 24" class="h-6 w-6" fill="none" stroke="currentColor"><path d="M5 13l4 4L19 7"/></svg>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16z" clip-rule="evenodd"/></svg>
{{-- environment + auth gates --}}
@production
<script>/* analytics */</script>
@endproduction
@env(['local','staging'])
<div class="debug-ribbon">{{app()->environment()}}</div>
@endenv
@hasSection('banner')
<div class="banner">@yield('banner')</div>
@endif
@sectionMissing('banner')
<div class="banner banner--default">Welcome!</div>
@endif
@can('update',$post)
<a href="{{route('posts.edit',$post)}}">Edit</a>
@elsecan('view',$post)
<a href="{{route('posts.show',$post)}}">View</a>
@cannot('delete',$post)
<span>Cannot delete</span>
@endcan
{{-- lang + choice --}}
<p>@lang('messages.welcome',['name'=>$user->name])</p>
<p>{{trans_choice('messages.items',$count,['count'=>$count])}}</p>
{{-- session directive --}}
@session('success')
<div class="toast">{{$value}}</div>
@endsession
{{-- json into a script --}}
<script>
window.APP = @json($appConfig, JSON_PRETTY_PRINT);
const routes = @json($routes);
@if($debug) console.log('booting', !window.production); @endif
document.addEventListener('DOMContentLoaded', () => {
    console.log('ready', {{ $user->id }});
});
</script>
{{-- a raw pre block that must be preserved --}}
<pre @class(['code'])>
function greet(name) {
    return "Hello, " + name;
}
</pre>
{{-- a nested @php block computing a summary --}}
@php
$summary = [
'total' => $total,
'active' => $activeCount,
'ratio' => $total > 0 ? round($activeCount / $total, 2) : 0,
];
@endphp
<footer class="stats">
<span>Total: {{$summary['total']}}</span>
<span>Active: {{$summary['active']}}</span>
<span>Ratio: {{$summary['ratio']}}</span>
</footer>
{{-- notifications dropdown with nested loops --}}
<div x-data="{open:false}" class="notifications">
<button @click="open=!open" :aria-expanded="open">
Notifications
@if($unreadCount>0)
<span class="dot">{{$unreadCount}}</span>
@endif
</button>
<ul x-show="open" @click.away="open=false" x-cloak>
@forelse($notifications as $notification)
<li @class(['note','note--unread'=>is_null($notification->read_at)]) wire:key="note-{{$notification->id}}">
@switch($notification->type)
@case('mention')
<strong>{{$notification->actor}}</strong> mentioned you
@break
@case('comment')
<strong>{{$notification->actor}}</strong> commented
@break
@default
<strong>{{$notification->actor}}</strong> {{$notification->description}}
@endswitch
<time>{{$notification->created_at->diffForHumans()}}</time>
</li>
@empty
<li class="muted">You're all caught up.</li>
@endforelse
</ul>
</div>
{{-- an FAQ accordion --}}
<section class="faq" x-data="{open:null}">
<h2>Frequently asked questions</h2>
@foreach($faqs as $index=>$faq)
<div class="faq__item">
<button @click="open === {{$index}} ? open = null : open = {{$index}}" :aria-expanded="open==={{$index}}">
{{$faq['question']}}
</button>
<div x-show="open==={{$index}}" x-collapse x-cloak>
<p>{{$faq['answer']}}</p>
@isset($faq['link'])
<a href="{{$faq['link']}}">Learn more</a>
@endisset
</div>
</div>
@endforeach
</section>
{{-- a pricing table with nested loops --}}
<section class="pricing">
@foreach($plans as $plan)
<div @class(['plan','plan--popular'=>$plan->popular])>
<h3>{{$plan->name}}</h3>
<p class="price">{{Money::format($plan->price)}}<span>/{{$plan->interval}}</span></p>
<ul>
@foreach($plan->features as $feature)
<li @class(['feature','feature--included'=>$feature->included])>
@if($feature->included) ✓ @else ✗ @endif
{{$feature->label}}
</li>
@endforeach
</ul>
<form method="POST" action="{{route('subscribe',$plan)}}">
@csrf
<button type="submit" @disabled(!$plan->available)>
@if($user?->subscribedTo($plan))
Current plan
@else
Choose {{$plan->name}}
@endif
</button>
</form>
</div>
@endforeach
</section>
{{-- a threaded comments list --}}
<section class="comments">
<h2>{{trans_choice('comments.count',$comments->count(),['count'=>$comments->count()])}}</h2>
@forelse($comments as $comment)
<article class="comment" wire:key="comment-{{$comment->id}}">
<header>
<img src="{{$comment->author->avatar_url}}" alt="{{$comment->author->name}}" class="avatar">
<strong>{{$comment->author->name}}</strong>
@if($comment->author->is($post->author))
<span class="badge">Author</span>
@endif
<time datetime="{{$comment->created_at->toIso8601String()}}">{{$comment->created_at->diffForHumans()}}</time>
</header>
<div class="comment__body">{!! $comment->rendered_body !!}</div>
@can('reply',$comment)
<button wire:click="reply({{$comment->id}})">Reply</button>
@endcan
@if($comment->replies->isNotEmpty())
<div class="comment__replies">
@foreach($comment->replies as $reply)
<article class="comment comment--reply" wire:key="reply-{{$reply->id}}">
<header>
<strong>{{$reply->author->name}}</strong>
<time>{{$reply->created_at->diffForHumans()}}</time>
</header>
<div class="comment__body">{{$reply->body}}</div>
</article>
@endforeach
</div>
@endif
</article>
@empty
<p class="muted">Be the first to comment.</p>
@endforelse
</section>
{{-- a settings form with fieldsets --}}
<form method="POST" action="{{route('settings.update')}}" class="settings">
@csrf
@method('PATCH')
<fieldset>
<legend>Profile</legend>
@foreach($profileFields as $field)
<div class="field">
<label for="{{$field['id']}}">{{$field['label']}}</label>
<input type="{{$field['type']}}" id="{{$field['id']}}" name="{{$field['name']}}" value="{{old($field['name'],$field['value'])}}" @required($field['required'])>
@error($field['name'])
<p class="error">{{$message}}</p>
@enderror
</div>
@endforeach
</fieldset>
<fieldset>
<legend>Preferences</legend>
@foreach($preferences as $preference)
<label class="toggle">
<input type="checkbox" name="preferences[{{$preference['key']}}]" @checked($preference['enabled'])>
{{$preference['label']}}
</label>
@endforeach
</fieldset>
<fieldset>
<legend>Notifications</legend>
<select name="digest">
@foreach(['daily'=>'Daily','weekly'=>'Weekly','never'=>'Never'] as $value=>$label)
<option value="{{$value}}" @selected(old('digest',$user->digest)===$value)>{{$label}}</option>
@endforeach
</select>
</fieldset>
<button type="submit" @disabled($saving)>Save settings</button>
</form>
{{-- a stats dashboard driven by @foreach + @switch --}}
<section class="dashboard">
@foreach($widgets as $widget)
<div @class(['widget','widget--'.$widget->size]) wire:key="widget-{{$widget->id}}">
<h4>{{$widget->title}}</h4>
@switch($widget->type)
@case('metric')
<p class="metric">{{$widget->formattedValue}}</p>
@if($widget->trend>0)
<span class="trend trend--up">▲ {{$widget->trend}}%</span>
@elseif($widget->trend<0)
<span class="trend trend--down">▼ {{abs($widget->trend)}}%</span>
@else
<span class="trend">—</span>
@endif
@break
@case('list')
<ul>
@foreach($widget->rows as $row)
<li>{{$row->label}}: <strong>{{$row->value}}</strong></li>
@endforeach
</ul>
@break
@case('chart')
<div wire:ignore x-data="chart(@js($widget->series))" x-init="render()"></div>
@break
@default
<p class="muted">Unsupported widget.</p>
@endswitch
</div>
@endforeach
</section>
{{-- a timeline --}}
<ol class="timeline">
@foreach($events as $event)
<li @class(['event','event--milestone'=>$event->milestone])>
<span class="dot"></span>
<div>
<time>{{$event->happened_at->format('M j, Y')}}</time>
<p>{{$event->summary}}</p>
@unless($event->attachments->isEmpty())
<ul class="attachments">
@foreach($event->attachments as $attachment)
<li><a href="{{$attachment->url}}" download>{{$attachment->name}}</a></li>
@endforeach
</ul>
@endunless
</div>
</li>
@endforeach
</ol>
{{-- a kanban board with nested columns and cards --}}
<div class="board">
@foreach($columns as $column)
<div class="board__column" wire:key="column-{{$column->id}}" x-data="{dragging:false}">
<header @class(['board__header','is-empty'=>$column->cards->isEmpty()])>
<h3>{{$column->name}}</h3>
<span class="count">{{$column->cards->count()}}</span>
</header>
<div class="board__cards" @drop="dragging=false" @dragover.prevent>
@forelse($column->cards as $card)
<article class="board__card" draggable="true" wire:key="card-{{$card->id}}" @dragstart="dragging=true">
<h4>{{$card->title}}</h4>
@if($card->assignee)
<img src="{{$card->assignee->avatar_url}}" alt="{{$card->assignee->name}}" title="{{$card->assignee->name}}" class="avatar avatar--xs">
@endif
@if($card->labels->isNotEmpty())
<ul class="labels">
@foreach($card->labels as $label)
<li style="--label-color: {{$label->color}}">{{$label->name}}</li>
@endforeach
</ul>
@endif
<footer>
<time>{{$card->due_at?->format('M j') ?? 'No due date'}}</time>
@if($card->due_at?->isPast())
<span class="overdue">Overdue</span>
@endif
</footer>
</article>
@empty
<p class="muted">Drop cards here.</p>
@endforelse
</div>
</div>
@endforeach
</div>
{{-- a multi-step wizard driven by a for loop --}}
<div class="wizard" x-data="{step:1, total:{{count($steps)}}}">
<ol class="wizard__nav">
@for($step=1;$step<=count($steps);$step++)
<li @class(['step','step--done'=>$step<$current,'step--current'=>$step===$current])>
<span class="index">{{$step}}</span>
<span class="label">{{$steps[$step-1]['label']}}</span>
</li>
@endfor
</ol>
<div class="wizard__body">
@foreach($steps as $index=>$step)
<section x-show="step==={{$index+1}}" x-transition>
<h2>{{$step['heading']}}</h2>
@foreach($step['fields'] as $field)
<div class="field">
<label>{{$field['label']}}</label>
<input type="{{$field['type']}}" name="{{$field['name']}}" @required($field['required'] ?? false)>
</div>
@endforeach
</section>
@endforeach
</div>
<footer class="wizard__actions">
<button @click="step = Math.max(1, step - 1)" :disabled="step===1">Back</button>
<button @click="step = Math.min(total, step + 1)" :disabled="step===total">Next</button>
</footer>
</div>
{{-- a media gallery --}}
<div class="gallery grid grid-cols-2 gap-2 md:grid-cols-4">
@foreach($media as $asset)
<figure @class(['gallery__item','gallery__item--video'=>$asset->isVideo()]) wire:key="asset-{{$asset->id}}">
@if($asset->isVideo())
<video src="{{$asset->url}}" controls preload="none" poster="{{$asset->poster_url}}"></video>
@else
<img src="{{$asset->thumbnail_url}}" alt="{{$asset->alt}}" loading="lazy" width="{{$asset->width}}" height="{{$asset->height}}">
@endif
@isset($asset->caption)
<figcaption>{{$asset->caption}}</figcaption>
@endisset
</figure>
@endforeach
</div>
{{-- a filters sidebar --}}
<aside class="filters">
<form method="GET" action="{{url()->current()}}">
@foreach($filterGroups as $group)
<fieldset>
<legend>{{$group['title']}}</legend>
@foreach($group['options'] as $option)
<label>
<input type="checkbox" name="{{$group['key']}}[]" value="{{$option['value']}}" @checked(in_array($option['value'],request()->input($group['key'],[])))>
{{$option['label']}}
<span class="count">({{$option['count']}})</span>
</label>
@endforeach
</fieldset>
@endforeach
<div class="range">
<label>Min price</label>
<input type="number" name="min" value="{{request('min')}}" min="0" step="1">
<label>Max price</label>
<input type="number" name="max" value="{{request('max')}}" min="0" step="1">
</div>
<button type="submit">Apply</button>
@if(request()->hasAny(['min','max']) || collect($filterGroups)->pluck('key')->contains(fn($k)=>request()->filled($k)))
<a href="{{url()->current()}}">Clear filters</a>
@endif
</form>
</aside>
{{-- a mega footer with columns --}}
<footer class="mega-footer">
@foreach($footerColumns as $footerColumn)
<div class="mega-footer__column">
<h4>{{$footerColumn['heading']}}</h4>
<ul>
@foreach($footerColumn['links'] as $footerLink)
<li>
<a href="{{$footerLink['url']}}" @if($footerLink['external'] ?? false) target="_blank" rel="noopener" @endif>
{{$footerLink['label']}}
</a>
</li>
@endforeach
</ul>
</div>
@endforeach
</footer>
{{-- a calendar grid built from nested loops --}}
<table class="calendar">
<caption>{{$month->format('F Y')}}</caption>
<thead>
<tr>
@foreach($weekdays as $weekday)
<th abbr="{{$weekday['full']}}">{{$weekday['short']}}</th>
@endforeach
</tr>
</thead>
<tbody>
@foreach($weeks as $week)
<tr>
@foreach($week as $day)
<td @class(['day','day--today'=>$day['isToday'],'day--outside'=>!$day['inMonth'],'day--busy'=>count($day['events'])>0])>
<span class="day__number">{{$day['number']}}</span>
@if(count($day['events'])>0)
<ul class="day__events">
@foreach($day['events'] as $event)
<li @class(['event','event--all-day'=>$event['allDay']]) title="{{$event['title']}}">
@unless($event['allDay'])
<time>{{$event['start']}}</time>
@endunless
{{Str::limit($event['title'],18)}}
</li>
@endforeach
</ul>
@endif
</td>
@endforeach
</tr>
@endforeach
</tbody>
</table>
{{-- a chat message thread --}}
<div class="chat" x-data="{draft:''}">
<div class="chat__log">
@foreach($messages as $message)
<div @class(['message','message--mine'=>$message->user->is(auth()->user()),'message--system'=>$message->system]) wire:key="msg-{{$message->id}}">
@unless($message->system)
<img src="{{$message->user->avatar_url}}" alt="{{$message->user->name}}" class="avatar avatar--xs">
@endunless
<div class="message__bubble">
@if($message->system)
<em>{{$message->body}}</em>
@else
<strong>{{$message->user->name}}</strong>
<p>{!! nl2br(e($message->body)) !!}</p>
@if($message->attachments->isNotEmpty())
@foreach($message->attachments as $attachment)
<a href="{{$attachment->url}}" class="chip">📎 {{$attachment->name}}</a>
@endforeach
@endif
@endif
<time>{{$message->created_at->format('H:i')}}</time>
</div>
</div>
@endforeach
</div>
<form wire:submit.prevent="send" class="chat__composer">
<textarea x-model="draft" @keydown.enter.exact.prevent="$wire.send(draft); draft=''" placeholder="Type a message"></textarea>
<button type="submit" :disabled="draft.trim().length===0">Send</button>
</form>
</div>
{{-- a KPI row --}}
<div class="kpis grid grid-cols-2 gap-4 lg:grid-cols-4">
@foreach($kpis as $kpi)
<div class="kpi" wire:key="kpi-{{$loop->index}}">
<p class="kpi__label">{{$kpi['label']}}</p>
<p class="kpi__value">{{$kpi['value']}}</p>
@isset($kpi['delta'])
<p @class(['kpi__delta','kpi__delta--up'=>$kpi['delta']>=0,'kpi__delta--down'=>$kpi['delta']<0])>
{{$kpi['delta']>=0?'+':''}}{{$kpi['delta']}}%
</p>
@endisset
</div>
@endforeach
</div>
{{-- breadcrumbs --}}
<nav aria-label="Breadcrumb" class="breadcrumbs">
<ol>
@foreach($breadcrumbs as $crumb)
<li @class(['crumb','crumb--current'=>$loop->last]) @if($loop->last) aria-current="page" @endif>
@if($loop->last)
{{$crumb['label']}}
@else
<a href="{{$crumb['url']}}">{{$crumb['label']}}</a>
@endif
</li>
@endforeach
</ol>
</nav>
{{-- an invoice with line items and totals --}}
<article class="invoice">
<header>
<h2>Invoice #{{$invoice->number}}</h2>
<p>Issued {{$invoice->issued_at->format('M j, Y')}}</p>
@if($invoice->paid)
<span class="badge badge-green">Paid</span>
@elseif($invoice->overdue)
<span class="badge badge-red">Overdue</span>
@else
<span class="badge badge-yellow">Due {{$invoice->due_at->diffForHumans()}}</span>
@endif
</header>
<table>
<thead>
<tr><th>Item</th><th>Qty</th><th>Unit</th><th>Total</th></tr>
</thead>
<tbody>
@foreach($invoice->lineItems as $line)
<tr>
<td>
{{$line->description}}
@isset($line->note)
<small>{{$line->note}}</small>
@endisset
</td>
<td>{{$line->quantity}}</td>
<td>{{Money::format($line->unitPrice)}}</td>
<td>{{Money::format($line->total)}}</td>
</tr>
@endforeach
</tbody>
<tfoot>
<tr><th colspan="3">Subtotal</th><td>{{Money::format($invoice->subtotal)}}</td></tr>
@if($invoice->discount>0)
<tr><th colspan="3">Discount</th><td>-{{Money::format($invoice->discount)}}</td></tr>
@endif
<tr><th colspan="3">Tax ({{$invoice->taxRate}}%)</th><td>{{Money::format($invoice->tax)}}</td></tr>
<tr class="total"><th colspan="3">Total</th><td>{{Money::format($invoice->total)}}</td></tr>
</tfoot>
</table>
</article>
{{-- a recursive-looking nested menu tree --}}
<nav class="tree">
<ul>
@foreach($menu as $node)
<li @class(['node','node--has-children'=>!empty($node['children'])])>
<a href="{{$node['url']}}">{{$node['label']}}</a>
@if(!empty($node['children']))
<ul>
@foreach($node['children'] as $child)
<li>
<a href="{{$child['url']}}" @class(['active'=>request()->fullUrlIs($child['url'])])>{{$child['label']}}</a>
@if(!empty($child['badge']))
<span class="badge">{{$child['badge']}}</span>
@endif
</li>
@endforeach
</ul>
@endif
</li>
@endforeach
</ul>
</nav>
</main>
@section('footer')
<footer class="mt-12 border-t py-6 text-center text-sm text-gray-500">© {{date('Y')}} {{config('app.name')}}</footer>
@show
@stack('scripts')
</body>
</html>
