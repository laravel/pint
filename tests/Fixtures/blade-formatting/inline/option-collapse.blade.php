<select {{$attributes}}>
@foreach (   $options as $value => $label   )
<option value="{{$value}}" {{$value == $selected ? 'selected' : ''}}>
{{$label}}
</option>
@endforeach
</select>
