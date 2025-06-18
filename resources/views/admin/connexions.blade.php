@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h3>Historique des connexions</h3>
    <div class="table-responsive mt-3">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>IP</th>
                    <th>Device (User-Agent)</th>
                    <th>Dernière activité</th>
                </tr>
            </thead>
            <tbody>
            @forelse($connexions as $connexion)
                <tr>
                    <td>
                        @if($connexion->prenom || $connexion->nom)
                            {{ $connexion->prenom ?? '' }} {{ $connexion->nom ?? '' }}
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td>
                        {{ $connexion->email ?? '-' }}
                    </td>
                    <td>
                        {{ $connexion->ip_address ?? '-' }}
                    </td>
                    <td style="max-width: 250px; word-break: break-all;">
                        <small>{{ \Illuminate\Support\Str::limit($connexion->user_agent, 120) }}</small>
                    </td>
                    <td>
                        @if($connexion->last_activity)
                            {{ \Carbon\Carbon::createFromTimestamp($connexion->last_activity)->format('d/m/Y H:i') }}
                            <br>
                            <small class="text-muted">{{ \Carbon\Carbon::createFromTimestamp($connexion->last_activity)->diffForHumans() }}</small>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center">Aucune connexion enregistrée.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
