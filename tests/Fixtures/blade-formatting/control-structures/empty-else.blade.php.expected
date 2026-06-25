<div>
    @if ($showVerificationStep)
        <div>verify step</div>
    @else
        <div class="qr">
            <div class="frame">
                @empty($qrCodeSvg)
                    <div>loading</div>
                @else
                    <div>{!! $qrCodeSvg !!}</div>
                @endempty
            </div>
        </div>

        <div class="manual">
            <div class="field">
                @empty($manualSetupKey)
                    <div>loading</div>
                @else
                    <div>{{ $manualSetupKey }}</div>
                    <div>copy</div>
                @endempty
            </div>
        </div>
    @endif
</div>
