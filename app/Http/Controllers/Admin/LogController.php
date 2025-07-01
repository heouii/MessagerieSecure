<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Process\Process;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $logs = [];
        $error = null;

        if ($request->has('date')) {
            $date = $request->input('date');

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $error = 'Format de date invalide.';
                return view('admin.logs.index', compact('logs', 'error'));
            }

            $jsonFile = base_path("storage/logs/filtered_logs_{$date}.json");
            $scriptPath = base_path('scripts/parse_logs.py');

            if (!file_exists($jsonFile)) {
                $process = new Process(['python3', $scriptPath, $date]);
                $process->run();

                if (!$process->isSuccessful()) {
                    $error = 'Erreur lors de l’exécution du script Python : ' . $process->getErrorOutput();
                    return view('admin.logs.index', compact('logs', 'error'));
                }
            }

            if (file_exists($jsonFile)) {
                $content = file_get_contents($jsonFile);
                $logs = json_decode($content, true);
            } else {
                $error = "Le fichier JSON des logs n'a pas été trouvé après exécution du script.";
            }
        }

        return view('admin.logs.index', compact('logs', 'error'));
    }

    public function export(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'date' => 'required|date_format:Y-m-d',
        ]);

        if (!Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        $date = $request->input('date');
        $jsonFile = base_path("storage/logs/filtered_logs_{$date}.json");

        if (file_exists($jsonFile)) {
            return response()->download($jsonFile, "logs_{$date}.json");
        }

        return back()->with('error', 'Fichier de logs non trouvé pour export.');
    }
}
