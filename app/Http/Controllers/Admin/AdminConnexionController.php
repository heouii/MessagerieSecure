<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AdminConnexionController extends Controller
{
    public function index()
    {
        $connexions = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select(
                'sessions.*',
                'users.nom',
                'users.prenom',
                'users.email'
            )
            ->orderByDesc('last_activity')
            ->limit(100)
            ->get();

        return view('admin.connexions', compact('connexions'));
    }
}
