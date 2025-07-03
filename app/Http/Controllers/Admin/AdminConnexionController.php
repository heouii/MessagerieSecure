<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use App\Models\User;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class AdminConnexionController extends Controller
{
    public function index()
    {
        $sessionsPath = storage_path('framework/sessions');
        $sessionFiles = File::files($sessionsPath);
        $connexions = [];

        foreach ($sessionFiles as $file) {
            $content = File::get($file);
            $data = @unserialize($content);

            if ($data && is_array($data)) {
                $userId = null;

                foreach ($data as $key => $value) {
                    if (is_int($value) && $value > 0) {
                        $userId = $value;
                        break;
                    }
                }

                $user = $userId ? User::find($userId) : null;
                $lastActivity = Carbon::createFromTimestamp($file->getMTime(), 'UTC')->setTimezone('Europe/Paris');

                $connexions[] = (object)[
                    'prenom' => $user->prenom ?? null,
                    'nom' => $user->nom ?? null,
                    'email' => $user->email ?? null,
                    'last_activity' => $lastActivity,
                ];
            }
        }

        usort($connexions, function($a, $b) {
            return $b->last_activity->timestamp <=> $a->last_activity->timestamp;
        });

        $connexions = collect($connexions);

        return view('admin.connexions', compact('connexions'));
    }
}
