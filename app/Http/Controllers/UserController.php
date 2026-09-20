<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Image;

class UserController extends Controller
{
    /**
     * Shows the user profile of the given user.
     */
    public function show(Request $request, User $user)
    {
        $this->authorize('view', $user);

        return $request->wantsJson()
            ? response()->json($user)
            : response()->view('pages.profile', ['user' => $user]);
    }

    /**
     * Register a new user.
     * This endpoint is API only.
     *
     * @return JsonResponse The registered user, serialized.
     */
    public function store(StoreUserRequest $request)
    {

        $user = $this->storeUser($request);

        return response()->json($user->toArray(), 201);
    }

    public function storeUser(Request $request)
    {

        $data = $request->all();

        $user = new User;

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password'];
        $user->save();

        return $user;
    }

    public function update(UpdateUserRequest $request, User $user)
    {

        $this->authorize('update', $user);

        $user = $this->updateUser($user, $request);

        return $request->wantsJson()
            ? response()->json($user->toArray(), 201)
            : redirect()->route('user.profile', ['user' => $user]);
    }

    public function updateUser(User $user, Request $request)
    {

        $data = $request->all();

        if (($data['name'] ??= null) !== null) {
            $user->name = $data['name'];
        }

        if (isset($data['profile_picture'])) {
            Image::fromUpload($data['profile_picture'])
                ->orient()
                ->cover(512, 512)
                ->toWebp()
                ->storePubliclyAs('public/users', "$user->id.webp");
        }

        // Privilege fields belong to admins alone; block/unblock also have
        // dedicated endpoints.
        if ($request->user()->is_admin) {
            if (($data['is_admin'] ?? null) !== null) {
                $user->is_admin = $data['is_admin'];
            }

            if (($data['blocked'] ?? null) !== null) {
                $user->blocked = $data['blocked'];
            }
        }

        $user->save();

        return $user;
    }

    public function edit(User $user)
    {
        $this->authorize('showProfileEditPage', $user);

        return response()->view('pages.profile.edit', ['user' => $user]);
    }

    public function block(Request $request, User $user)
    {
        $this->authorize('block', $user);

        $user->blocked = true;
        $user->save();

        return $request->wantsJson()
            ? response()->json($user->toArray(), 200)
            : redirect()->route('home');
    }

    public function unblock(Request $request, User $user)
    {
        $this->authorize('unblock', $user);

        $user->blocked = false;
        $user->save();

        return $request->wantsJson()
            ? response()->json($user->toArray(), 200)
            : redirect()->route('home');
    }

    public function showReportForm(User $user)
    {
        $this->authorize('report', $user);

        return view('pages.reportuser', ['user' => $user]);
    }

    public function report(StoreReportRequest $request, User $user)
    {
        $this->authorize('report', $user);

        $report = new Report;

        $report->reason = $request->input('reason');
        $report->user_profile_id = $user->id;
        $report->creator_id = $request->user()->id;
        $report->save();

        return redirect()->route('user.profile', ['user' => $user]);
    }

    public function destroy(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $user->delete();

        return $request->wantsJson()
            ? response()->json($user->toArray(), 200)
            : redirect()->route('home');
    }
}
