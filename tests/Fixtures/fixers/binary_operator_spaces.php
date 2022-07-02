<?php

$array = [
    'long_item_name' =>  'value',
    'short'          =>  'value',
];

$array = array_filter($array, fn ($item)  =>  $item === 'value');
