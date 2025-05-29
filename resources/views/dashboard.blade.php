@extends('layouts.app')

@section('content')

{{-- Alertes de succès ou erreurs --}}
@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Bienvenue, {{ auth()->user()->prenom }} {{ auth()->user()->nom }}</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nouveauMessage">
        <i class="fas fa-plus"></i> Nouveau Message
    </button>
</div>

<div class="row">
    @foreach($messages as $message)
    <div class="col-md-6">
        <div class="card mb-4 shadow-sm">
            <div class="card-header">
                <a href="{{ route('messages.show', $message->id) }}">
                    <strong>{{ $message->sujet }}</strong>
                </a>
            </div>
            <div class="card-body">
                <p class="card-text">{{ \Illuminate\Support\Str::limit($message->contenu, 100) }}</p>
                <small class="text-muted">Reçu le {{ $message->created_at->format('d/m/Y à H:i') }}</small>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="modal fade" id="nouveauMessage" tabindex="-1" aria-labelledby="nouveauMessageLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('messages.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="nouveauMessageLabel">Nouveau Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="destinataire" class="form-label">Destinataire</label>
                        <input type="email" name="destinataire" class="form-control" id="destinataire" required placeholder="Entrez l'email du destinataire">
                    </div>
                    <div class="mb-3">
                        <label for="sujet" class="form-label">Sujet</label>
                        <input type="text" name="sujet" class="form-control" id="sujet" required>
                    </div>
                    <div class="mb-3">
                        <label for="contenu" class="form-label">Contenu</label>
                        <textarea name="contenu" class="form-control" id="contenu" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="piece_jointe" class="form-label">Pièce jointe (facultative)</label>
                        <input type="file" name="piece_jointe" class="form-control" id="piece_jointe">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" >Envoyer</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
