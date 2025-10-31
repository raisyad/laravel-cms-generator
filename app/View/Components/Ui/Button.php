<?php

namespace App\View\Components\Ui;

use Illuminate\View\Component;

class Button extends Component
{
    public function __construct(
        public string $variant = 'primary',
        public string $as = 'button',
        public string $size = 'md',
        public bool $block = false,
    ) {}

    public function render()
    {
        return view('components.ui.button');
    }
}
