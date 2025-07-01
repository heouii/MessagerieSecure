@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Détails du Message</h2>
    <a href="{{ route('dashboard') }}" class="btn btn-secondary">Retour</a>
</div>

<div class="card">
    <div class="card-header">
        <strong>Sujet : </strong>{{ $message->sujet }}
    </div>
    <div class="card-body">
        <p><strong>Expéditeur : </strong>{{ $message->user->prenom }} {{ $message->user->nom }}</p>
        <p><strong>Destinataire : </strong>{{ $message->receiver->prenom }} {{ $message->receiver->nom }}</p>
        <p><strong>Contenu : </strong>{{ $message->contenu }}</p>
        <p><small class="text-muted">Reçu le {{ $message->created_at->format('d/m/Y à H:i') }}</small></p>

        @if ($message->piece_jointe)
            <div>
                <strong>Pièce jointe : </strong>
                <a href="{{ Storage::url($message->piece_jointe) }}" target="_blank">Télécharger la pièce jointe</a>
            </div>
        @endif
    </div>
</div>

@endsection
