<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class ProfilePage extends Component
{
    public User $user;

    public function mount(User $user): void
    {
        $this->authorize('view', $user);

        $this->user = $user;
    }

    public function deleteUser(): void
    {
        $this->authorize('delete', $this->user);

        $this->user->delete();

        $this->redirectRoute('home', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.profile-page')->title($this->user->name);
    }
}
