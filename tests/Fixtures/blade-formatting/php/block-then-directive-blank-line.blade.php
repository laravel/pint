@foreach ($widgets as $widget)
    @php
        $widgetClass = $normalizeWidgetClass($widget);
    @endphp

    @livewire($widgetClass, ['widget' => $widget])
@endforeach
