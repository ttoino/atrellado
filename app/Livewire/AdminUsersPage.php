<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AdminUsersPage extends Component
{
    public function blockUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('block', $user);

        $user->blocked = true;
        $user->save();
    }

    public function unblockUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('unblock', $user);

        $user->blocked = false;
        $user->save();
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->authorize('delete', $user);

        $user->delete();
    }

    public function render(): View
    {
        $searchTerm = request()->query('q') ?? '';

        $users = User::withCount('reports');

        if ($searchTerm !== '') {
            $users = $users->where('name', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $searchTerm).'%');
        }

        return view('livewire.admin-users-page', [
            'users' => $users->cursorPaginate(10)->withQueryString(),
        ])->title('Users');
    }
}
