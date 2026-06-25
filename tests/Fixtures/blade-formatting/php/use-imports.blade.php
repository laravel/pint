@php
    use App\Enums\CarStatus;
    use App\Enums\FuelType;
    use App\Enums\TransmissionType;

    $car = $user->car;
    $owner = $car?->owner;
    $engine = $car->engine;
@endphp

    <div>{{$car}}</div>
