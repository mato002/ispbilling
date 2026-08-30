@if ($item->qrCodeUrl())
    <img
        src="{{ $item->qrCodeUrl() }}"
        alt="QR code for {{ $item->serial_number }}"
        width="{{ $size ?? 48 }}"
        height="{{ $size ?? 48 }}"
        class="rounded border border-[var(--pb-border)] bg-white p-0.5"
        loading="lazy"
        @if ($link ?? false)
            onclick="event.stopPropagation(); window.location='{{ route('isp.inventory.show', $item) }}'"
        @endif
    >
@else
    <span class="inline-flex h-12 w-12 items-center justify-center rounded border border-dashed border-slate-300 text-[10px] text-slate-400">QR</span>
@endif
