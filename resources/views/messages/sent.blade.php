@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Messages envoyés</h2>
    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Retour au tableau de bord
    </a>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

@if ($messages->isEmpty())
    <div class="alert alert-info">
        Aucun mail envoyé pour le moment.
    </div>
@else
    <div class="row">
        @foreach($messages as $message)
        <div class="col-md-6">
            <div class="card mb-4 shadow-sm">
                <div class="card-header">
                    <strong>{{ $message->sujet }}</strong>
                </div>
                <div class="card-body">
                    <p class="card-text">{{ \Illuminate\Support\Str::limit($message->contenu, 100) }}</p>
                    @if($message->piece_jointe)
                        <p><i class="fas fa-paperclip"></i> <a href="{{ asset('storage/pieces_jointes/' . $message->piece_jointe) }}" target="_blank">Voir la pièce jointe</a></p>
                    @endif
                    <p><strong>Destinataire : </strong>
                        @if($message->receiver)
                            {{ $message->receiver->prenom }} {{ $message->receiver->nom }}
                        @else
                            <span class="text-muted">Destinataire inconnu</span>
                        @endif
                    </p>
                    <small class="text-muted">Envoyé le {{ $message->created_at->format('d/m/Y à H:i') }}</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endif

@endsection
