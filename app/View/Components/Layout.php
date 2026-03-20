<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Layout extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $jsonLd = null,
        public ?string $ogImage = null,
        public ?string $ogType = 'website',
        public ?string $ogVideo = null,
        public ?string $ogVideoType = null,
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.layout');
    }
}
