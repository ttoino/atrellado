<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function show(Request $request)
    {

        $user = $request->user();

        if ($user === null) {
            return response()->view('pages.home');
        } elseif ($user->is_admin) {
            return redirect()->route('admin');
        } elseif ($user->is_blocked) {
            dd('bahhh');
        } // TODO: implement this
        else {
            return redirect()->route('project.list');
        }
    }
}
