<?php

namespace App\Livewire;

use Livewire\Component;

class Settings extends Component
{
    public string $newTokenName = '';

    public ?string $plainTextToken = null;

    public function createToken(): void
    {
        $this->validate([
            'newTokenName' => 'required|string|max:255',
        ]);

        $newToken = auth()->user()->createToken($this->newTokenName);

        $this->plainTextToken = $newToken->plainTextToken;
        $this->newTokenName = '';
    }

    public function revokeToken(int $tokenId): void
    {
        auth()->user()->tokens()->whereKey($tokenId)->delete();
        $this->plainTextToken = null;
    }

    public function dismissPlainTextToken(): void
    {
        $this->plainTextToken = null;
    }

    public function render()
    {
        return view('livewire.settings', [
            'tokens' => auth()->user()->tokens()->latest()->get(),
        ]);
    }
}
