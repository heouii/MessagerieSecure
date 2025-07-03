<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blacklist;
use Illuminate\Http\Request;

class BlacklistController extends Controller
{
    public function index()
    {
        $items = Blacklist::orderBy('created_at', 'desc')->paginate(15);
        return view('admin.blocagemail.blacklists.index', compact('items'));
    }

    public function create()
    {
        return view('admin.blocagemail.blacklists.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:email,domain',
            'value' => [
                'required',
                'string',
                'unique:blacklists,value',
                function ($attribute, $value, $fail) use ($request) {
                    $value = trim($value);
                    if ($request->type === 'domain') {

                        if (!filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                            $fail("La valeur doit être un domaine valide (ex: exemple.com).");
                            return;
                        }

                        if (!preg_match('/\.[a-z]{2,}$/i', $value)) {
                            $fail("Le domaine doit contenir une extension valide (ex: .com, .fr, .net).");
                        }
                    } elseif ($request->type === 'email') {
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $fail("La valeur doit être une adresse email valide.");
                        }
                    }
                }
            ],
        ]);

        Blacklist::create($request->only('type', 'value'));

        return redirect()->route('admin.blacklists.index')->with('success', 'Entrée ajoutée à la blacklist.');
    }

    public function edit(Blacklist $blacklist)
    {
        return view('admin.blocagemail.blacklists.edit', compact('blacklist'));
    }

    public function update(Request $request, Blacklist $blacklist)
    {
        $request->validate([
            'type' => 'required|in:email,domain',
            'value' => [
                'required',
                'string',
                'unique:blacklists,value,' . $blacklist->id,
                function ($attribute, $value, $fail) use ($request) {
                    $value = trim($value);
                    if ($request->type === 'domain') {
                        if (!filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                            $fail("La valeur doit être un domaine valide (ex: exemple.com).");
                            return;
                        }
                        if (!preg_match('/\.[a-z]{2,}$/i', $value)) {
                            $fail("Le domaine doit contenir une extension valide (ex: .com, .fr, .net).");
                        }
                    } elseif ($request->type === 'email') {
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $fail("La valeur doit être une adresse email valide.");
                        }
                    }
                }
            ],
        ]);

        $blacklist->update($request->only('type', 'value'));

        return redirect()->route('admin.blacklists.index')->with('success', 'Entrée modifiée.');
    }

    public function destroy(Blacklist $blacklist)
    {
        $blacklist->delete();

        return redirect()->route('admin.blacklists.index')->with('success', 'Entrée supprimée.');
    }
}
