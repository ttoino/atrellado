<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Image;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ProfileEditPage extends Component
{
    use WithFileUploads;

    public User $user;

    public string $name = '';

    public ?TemporaryUploadedFile $profile_picture = null;

    public function mount(User $user): void
    {
        $this->authorize('update', $user);

        $this->user = $user;
        $this->name = $user->name;
    }

    public function save(): void
    {
        $this->authorize('update', $this->user);

        $validated = $this->validate([
            'name' => 'string|min:6|max:255',
            'profile_picture' => [
                'nullable',
                File::image()
                    ->max(5 * 1024)
                    // Compressed size says nothing about the decoded
                    // bitmap; cap dimensions so GD decodes stay bounded.
                    ->dimensions(Rule::dimensions()->maxWidth(4000)->maxHeight(4000)),
            ],
        ]);

        $this->user->name = $validated['name'];

        if ($this->profile_picture !== null) {
            Image::fromUpload($this->profile_picture)
                ->orient()
                ->cover(512, 512)
                ->toWebp()
                ->storePubliclyAs('public/users', "{$this->user->id}.webp");
        }

        $this->user->save();

        $this->redirectRoute('user.profile', ['user' => $this->user], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.profile-edit-page')->title($this->user->name);
    }
}
