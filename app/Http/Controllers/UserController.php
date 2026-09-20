<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Report;
use App\Models\User;

class UserController extends Controller
{
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
}
