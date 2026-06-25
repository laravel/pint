<div>
<span @class([   'p-4', 'font-bold' => $active, 'text-red-500' => $hasError   ])>One</span>
        <button @class(["base", "active" => $isActive])>Two</button>
   <input type="checkbox" @checked(  $value  ) @disabled( $locked ) />
<option @selected(   $selected   )>Choice</option>
        <li @if(  $active  ) class="active" @endif>Item</li>
   <div @style(['color: red', 'font-weight: bold' => $bold])>Styled</div>
<a href="#" @class([   'nav-link', 'active' => request()->routeIs('home')   ])>Home</a>
</div>
