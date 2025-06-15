<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Message;
use Carbon\Carbon;

class AdminController extends Controller
{
    // S'assurer que l'utilisateur est bien authentifié (déjà mis dans la route, mais en sécurité ici aussi)
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function dashboard()
    {
        $user = Auth::user();

        if (!$user->admin) {
            abort(403);
        }

        // Stats
        $totalUsers = User::count();
        $totalAdmins = User::where('admin', 1)->count();
        $totalMessages = Message::count();
        $blockedUsersCount = User::whereNotNull('blocked_until')->where('blocked_until', '>', now())->count();

        // Derniers inscrits
        $recentUsers = User::orderBy('created_at', 'desc')->take(5)->get();

        // Graphique : utilisateurs par jour (7 derniers jours)
        $days = collect(range(6, 0))->map(function ($i) {
            return Carbon::now()->subDays($i);
        });

        $chartLabels = $days->map(function ($day) {
            return $day->format('d/m');
        });

        $chartData = $days->map(function ($day) {
            return User::whereDate('created_at', $day->format('Y-m-d'))->count();
        });

        return view('admin.dashboard', compact(
            'user',
            'totalUsers',
            'totalAdmins',
            'totalMessages',
            'blockedUsersCount', 
            'recentUsers',
            'chartLabels',
            'chartData'
        ));
    }

    public function users(){
    $user = Auth::user();

    if (!$user->admin) {
        abort(403);
    }

    // Pagination pour éviter d'afficher trop d'utilisateurs d'un coup
    $users = User::orderBy('created_at', 'desc')->paginate(20);

    return view('admin.users', compact('users'));
    }

    public function block(Request $request, User $user)
{
    $days = (int) $request->input('block_duration');
    if ($days === 0) {
        $user->blocked_until = null; // blocage indéfini
    } else {
        $user->blocked_until = now()->addDays($days);
    }
    $user->save();

    return redirect()->route('admin.users')->with('success', "Utilisateur bloqué pour $days jours.");
}


}
