<!DOCTYPE html>
<html lang="en">
<body>
<style>
        @foreach ($colors as $name => $hex)
            .text-{{ $name }} { color: {{ $hex }}; }
        @endforeach
</style>
</body>
</html>
