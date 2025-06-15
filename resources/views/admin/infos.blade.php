@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <h4 class="mb-0">Détails de l'utilisateur</h4>
        </div>
        <div class="card-body">
            <p><strong>Nom :</strong> {{ $user->prenom }} {{ $user->nom }}</p>
            <p><strong>Email :</strong> {{ $user->email }}</p>
            <p><strong>Téléphone :</strong> {{ $user->tel }}</p>
            <p><strong>Inscrit le :</strong> {{ $user->created_at->format('d/m/Y H:i') }}</p>
            <p><strong>Statut :</strong>
                @if ($user->blocked_until && $user->blocked_until->isFuture())
                    <span class="text-danger">Bloqué jusqu’au {{ $user->blocked_until->format('d/m/Y H:i') }}</span>
                @else
                    <span class="text-success">Actif</span>
                @endif
            </p>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.users') }}" class="btn btn-secondary">← Retour</a>
        </div>
    </div>
</div>
@endsection
