<?php

$int = 1;
$array = [];
$object = new stdClass();

if (count($array) === (count($array) - intval(isset($array['a'])))) {
    //
}

if ($object->count() === $int) {
    //
}

if (array_values($array) !== $array) {
    //
}

if ($this->guard === $guard && (int) $this->user_id === $userId) {
    //
}

if (null === $int) {
    //
}
