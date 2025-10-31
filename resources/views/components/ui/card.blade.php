@props(['as' => 'div', 'class' => ''])

<{{ $as }} {{ $attributes->merge(['class' => trim('card '.$class)]) }}>
  {{ $slot }}
</{{ $as }}>
