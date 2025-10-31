@props([
  'as'      => 'button',   // 'button' | 'a'
  'variant' => 'primary',  // primary | ghost | danger | outline | secondary
  'size'    => 'md',       // sm | md | lg
  'block'   => false,      // true -> w-full
])

@php
    // >>> NEW: auto-switch to anchor if href is present
  $renderAs = $attributes->has('href') ? 'a' : $as;

  $base = implode(' ', [
    // layout
    'inline-flex items-center justify-center gap-2 rounded-lg',
    // size (diisi di $sizes)
    // ring + focus
    'ring-1 ring-inset focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-0',
    // transisi & state umum
    'transition shadow-sm disabled:opacity-50 disabled:cursor-not-allowed',
    // teks
    'font-medium',
  ]);

  $sizes = [
    'sm' => 'px-2.5 py-1 text-xs',
    'md' => 'px-3.5 py-1.5 text-sm',
    'lg' => 'px-4.5 py-2 text-base',
  ];

  $variants = [
    'primary' => implode(' ', [
      'bg-indigo-600 text-white ring-indigo-600/10',
      'hover:bg-indigo-700',
      'focus-visible:ring-indigo-500/40',
      'dark:bg-indigo-500 dark:hover:bg-indigo-400 dark:text-white dark:ring-indigo-400/20',
    ]),
    'secondary' => implode(' ', [
      'bg-slate-900 text-white ring-slate-900/10',
      'hover:bg-black',
      'focus-visible:ring-slate-900/40',
      'dark:bg-slate-700 dark:hover:bg-slate-600 dark:ring-slate-600/30',
    ]),
    'outline' => implode(' ', [
      'bg-transparent text-slate-800 ring-slate-300',
      'hover:bg-slate-50',
      'focus-visible:ring-slate-400',
      'dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800 dark:focus-visible:ring-slate-600',
    ]),
    'ghost' => implode(' ', [
      'bg-transparent text-slate-700 ring-transparent',
      'hover:bg-slate-100',
      'focus-visible:ring-slate-400',
      'dark:text-slate-200 dark:hover:bg-slate-800 dark:focus-visible:ring-slate-600',
    ]),
    'danger' => implode(' ', [
      'bg-rose-600 text-white ring-rose-600/10',
      'hover:bg-rose-700',
      'focus-visible:ring-rose-500/40',
      'dark:bg-rose-500 dark:hover:bg-rose-400 dark:ring-rose-400/20',
    ]),
  ];

  $cls = trim(
    $base . ' ' .
    ($sizes[$size] ?? $sizes['md']) . ' ' .
    ($variants[$variant] ?? $variants['primary']) . ' ' .
    ($block ? 'w-full' : '')
  );

  $btnAttrs = $attributes->merge(['class' => $cls]);
@endphp

@if ($renderAs === 'a')
  <a {{ $btnAttrs }}>{{ $slot }}</a>
@else
  <button {{ $btnAttrs->merge(['type' => $attributes->get('type', 'button')]) }}>
    {{ $slot }}
  </button>
@endif
