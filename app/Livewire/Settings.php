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
    }

    public function resetTokenModal(): void
    {
        $this->plainTextToken = null;
        $this->newTokenName = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.settings', [
            'tokens' => auth()->user()->tokens()->latest()->get(),
            'endpoints' => $this->apiEndpoints(),
        ]);
    }

    /**
     * @return array<int, array{method: string, path: string, description: string, curl: string}>
     */
    private function apiEndpoints(): array
    {
        $base = rtrim(config('app.url'), '/');
        $make = fn (string $method, string $path, string $description) => [
            'method' => $method,
            'path' => $path,
            'description' => $description,
            'curl' => sprintf('curl -H "Authorization: Bearer <token>" %s%s', $base, $path),
        ];

        return [
            $make('GET', '/api/ping', 'Health check for PowerBI connection tests'),
            $make('GET', '/api/skills', 'All skills'),
            $make('GET', '/api/skills/{id}/users', 'Users who hold a given skill with their levels'),
            $make('GET', '/api/users', 'Staff list with skills, busyness and service function'),
            $make('GET', '/api/users/{id}/skills', 'One user\'s skills with levels'),
            $make('GET', '/api/projects', 'Projects with staff assignments'),
            $make('GET', '/api/stats/skills-gap', 'Aggregated skill counts per level for each skill'),
        ];
    }
}
