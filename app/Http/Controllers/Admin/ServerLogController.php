<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServerLogController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));

        $logs  = ['access' => []];
        $error = null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $error = 'Format de date invalide.';
            return view('admin.logs.admin_logs', compact('logs', 'error', 'date'));
        }

        $pythonPath = '/usr/bin/python3';
        $scriptPath = base_path('scripts/read_logs.py');

        // Commande avec chemin absolu python3
        $cmd = $pythonPath . ' ' 
             . escapeshellarg($scriptPath) . ' ' 
             . escapeshellarg('access') . ' ' 
             . escapeshellarg($date) 
             . ' 2>&1';

        \Log::debug("Commande index: $cmd");

        exec($cmd, $output, $returnVar);

        \Log::debug("Return var: $returnVar");
        \Log::debug("Output index: " . print_r($output, true));

        if ($returnVar !== 0) {
            $error = "Erreur lors de la lecture du log access : " . implode("\n", $output);
        } else {
            $logs['access'] = $output;
        }

        return view('admin.logs.admin_logs', compact('logs', 'error', 'date'));
    }

    public function download(Request $request)
    {
        $request->validate([
            'password' => 'required',
            'date'     => 'required|date_format:Y-m-d',
        ]);

        if (! Hash::check($request->input('password'), Auth::user()->password)) {
            return back()->withErrors(['password' => 'Mot de passe incorrect.']);
        }

        $date = $request->input('date');

        $pythonPath = '/usr/bin/python3';
        $scriptPath = base_path('scripts/read_logs.py');

        $cmd = $pythonPath . ' ' 
             . escapeshellarg($scriptPath) . ' ' 
             . escapeshellarg('access') . ' ' 
             . escapeshellarg($date) 
             . ' 2>&1';

        \Log::debug("Commande download: $cmd");

        exec($cmd, $output, $returnVar);

        \Log::debug("Return var download: $returnVar");
        \Log::debug("Output download: " . print_r($output, true));

        if ($returnVar !== 0) {
            return back()->withErrors(['error' => "Erreur lors de la lecture du log access : " . implode("\n", $output)]);
        }

        $filename = "access_log_{$date}.txt";

        return new StreamedResponse(function () use ($output) {
            $handle = fopen('php://output', 'w');
            foreach ($output as $line) {
                fwrite($handle, $line . PHP_EOL);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/plain',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
