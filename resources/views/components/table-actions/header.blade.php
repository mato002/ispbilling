<th {{ $attributes->merge(['class' => 'text-right']) }}>
    {{ $slot->isEmpty() ? 'Actions' : $slot }}
</th>
