<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si l'utilisateur est connecté et s'il est administrateur
        if (Auth::check() && Auth::user()->admin == 1) {
            return $next($request);  // L'utilisateur est un administrateur, donc il peut continuer
        }

        // Si l'utilisateur n'est pas administrateur, le rediriger vers le dashboard
        return redirect('/dashboard');
    }
}
