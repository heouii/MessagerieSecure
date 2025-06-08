@extends('layouts.app')

@section('content')
<div class="container py-5">
    <h2 class="mb-4 fw-bold" style="color: #BAA8D3;">Logs par date</h2>

    <form action="{{ route('admin.logs') }}" method="GET" class="mb-5">
        <div class="row g-3 align-items-center">
            <div class="col-auto">
                <label for="date" class="col-form-label fw-semibold">Choisissez une date :</label>
            </div>
            <div class="col-auto">
                <input 
                    type="date" 
                    name="date" 
                    id="date" 
                    value="{{ request('date') }}" 
                    class="form-control form-control-lg" 
                    required
                >
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-lg" style="background-color: #BAA8D3; color: white;">
                    Afficher les logs
                </button>
            </div>
        </div>
    </form>

    @if(isset($error))
        <div class="alert alert-danger shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ $error }}
        </div>
    @endif

    @isset($logs)
        <h4 class="mb-3 text-secondary">Résultats pour la date : <span class="text-dark">{{ request('date') }}</span></h4>

        @if(count($logs) > 0)
            <div class="mb-4">

                {{-- Formulaire de téléchargement protégé par mot de passe --}}
                <form action="{{ route('admin.logs.export') }}" method="POST" class="mb-4">
                    @csrf

                    <input type="hidden" name="date" value="{{ request('date') }}">

                    <div class="mb-3" style="max-width: 300px;">
                        <label for="password" class="form-label">Mot de passe pour télécharger :</label>
                        <input type="password" name="password" id="password" class="form-control" required>
                        @error('password')
                            <div class="text-danger mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="btn btn-success"style="background-color: #BAA8D3; color: white;">Télécharger les logs</button>
                </form>

                <div class="border rounded p-3 bg-light" style="max-height: 500px; overflow-y: auto; font-family: 'Fira Mono', monospace; font-size: 0.9rem; white-space: pre-wrap;">
                    @foreach ($logs as $line)
                        <div class="mb-1">{{ $line }}</div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="alert alert-info">
                <i class="bi bi-info-circle-fill me-2"></i>Aucune ligne trouvée pour cette date.
            </div>
        @endif
    @endisset
</div>
@endsection
