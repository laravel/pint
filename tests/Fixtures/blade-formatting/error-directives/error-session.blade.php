<form>
        <input type="email" name="email" class="@error('email') border-red-500 @enderror" />
   @error(  'email'  )
<span class="text-red-500">{{   $message   }}</span>
   @enderror
        @error(   'password'   )
<span>{{  $message  }}</span>
        @else
<span>Looks good</span>
        @enderror
   @session(  'status'  )
<div class="alert">{{   $value   }}</div>
   @endsession
</form>
