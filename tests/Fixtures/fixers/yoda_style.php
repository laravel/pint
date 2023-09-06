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

if ($object->int === $int && (int) $object->int === $int) {
    //
}

if (null === $int) {
    //
}
