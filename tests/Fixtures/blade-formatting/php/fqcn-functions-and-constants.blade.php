<div>
    <?php
    $total = \App\Support\money(1000);
    $currency = \App\Support\CURRENCY;
    $len = \strlen($currency);
    ?>

    <span>{{ $total }} {{ $currency }} {{ $len }}</span>
</div>
