<?php

use Livewire\Component;

new class extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function decrement(): void
    {
        $this->count--;
    }
};
?>

<div class="flex items-center gap-4 p-4 bg-white dark:bg-gray-800 rounded-lg shadow">
    <button wire:click="decrement" class="px-3 py-1 bg-red-500 text-white rounded hover:bg-red-600">-</button>
    <span class="text-xl font-bold text-gray-800 dark:text-white">{{ $count }}</span>
    <button wire:click="increment" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">+</button>
    <span class="text-sm text-gray-500 dark:text-gray-400">Livewire Counter</span>
</div>
