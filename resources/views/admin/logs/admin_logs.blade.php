@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h2 class="mb-4 fw-bold text-primary"style="color: #BAA8D3;">Logs serveur</h2>

    <form method="GET" class="row g-3 align-items-center mb-4">
        <div class="col-auto">
            <label for="date" class="form-label fw-semibold">Date :</label>
            <input type="date" name="date" id="date" value="{{ $date }}" class="form-control">
        </div>

        <div class="col-auto align-self-end">
            <button type="submit" class="btn btn-primary px-4">Afficher</button>
        </div>
    </form>

    <button type="button" class="btn btn-success mb-4" data-bs-toggle="modal" data-bs-target="#downloadModal">
        <i class="bi bi-download me-1"></i> Télécharger les logs
    </button>

    {{-- Modal téléchargement --}}
    <div class="modal fade" id="downloadModal" tabindex="-1" aria-labelledby="downloadModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('admin.server.logs.download') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="downloadModalLabel">Confirmer le téléchargement</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <p>Veuillez entrer votre mot de passe pour confirmer :</p>
                        <input type="password" name="password" class="form-control mb-3" required>

                        <input type="hidden" name="date" value="{{ $date }}">
                        <input type="hidden" name="keyword" value="{{ request('keyword') }}">
                        <input type="hidden" name="ip" value="{{ request('ip') }}">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Télécharger</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @if(isset($error))
        <div class="alert alert-danger shadow-sm d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i> 
            <span>{{ $error }}</span>
        </div>
    @endif

    @foreach (['access' => 'Logs d’accès', 'error' => 'Logs d’erreur'] as $type => $label)
        <section class="mb-5">
            <h4 class="text-secondary mb-3">{{ $label }} — {{ $date }}</h4>
            @if (!empty($logs[$type]))
                <div class="log-container p-3 bg-light border rounded">
                    @foreach ($logs[$type] as $line)
                        <pre class="mb-1 small text-break">{{ $line }}</pre>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info d-flex align-items-center">
                    <i class="bi bi-info-circle-fill fs-5 me-2"></i> Aucune ligne trouvée.
                </div>
            @endif
        </section>
    @endforeach
</div>

<style>
    .log-container {
        max-height: 400px;
        overflow-y: auto;
        font-family: "Courier New", Courier, monospace;
        font-size: 0.85rem;
        line-height: 1.3;
        white-space: pre-wrap;
        word-wrap: break-word;
        scrollbar-width: thin;
        scrollbar-color: #8a8a8a #e0e0e0;
    }
    .log-container::-webkit-scrollbar {
        width: 8px;
    }
    .log-container::-webkit-scrollbar-track {
        background: #e0e0e0;
        border-radius: 4px;
    }
    .log-container::-webkit-scrollbar-thumb {
        background-color: #8a8a8a;
        border-radius: 4px;
    }
</style>
@endsection
