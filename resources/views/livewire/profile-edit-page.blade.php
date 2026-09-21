<form wire:submit="save" class="m-auto vstack gap-3 p-3" style="max-width: 480px">
    <label class="align-self-center" style="cursor: pointer">
        <img src="{{ $profile_picture ? $profile_picture->temporaryUrl() : asset($user->profile_pic) }}"
            width=240 height=240 alt="{{ $user->name }}'s profile picture" class="rounded-circle">
        <input class="visually-hidden" type="file" wire:model="profile_picture" accept="image/*">
    </label>
    @error('profile_picture')
        <div class="text-danger text-center">{{ $message }}</div>
    @enderror

    <div class="form-floating">
        <input aria-describedby="name-feedback" placeholder=""
            class="form-control @error('name') is-invalid @enderror" id="name" type="text"
            wire:model="name" required autofocus>
        <label for="name" class="form-label">Name</label>
        <div class="invalid-feedback" id="name-feedback">
            @error('name')
                {{ $message }}
            @else
                Invalid name
            @enderror
        </div>
    </div>

    <div class="form-floating">
        <input aria-describedby="email-feedback" placeholder="" class="form-control" id="email" type="email"
            name="email" value="{{ $user->email }}" readonly disabled>
        <label for="email" class="form-label">E-mail</label>
    </div>

    <button type="submit" class="btn btn-primary">
        Edit
    </button>
</form>
