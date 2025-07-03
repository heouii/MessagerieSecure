@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="card shadow-sm border rounded-3">
        <div class="card-header" style="background: linear-gradient(45deg, #6f42c1, #5a3ea1); color: #fff;">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Historique des connexions</h3>
                <small class="text-light">{{ $connexions->count() }} connexions affichées</small>
            </div>
        </div>
        <div class="card-body p-0 bg-white">
            @if($connexions->isEmpty())
                <div class="alert alert-info m-4 text-center fs-5">
                    <i class="fas fa-info-circle me-2"></i> Aucune connexion enregistrée.
                </div>
            @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="border-collapse: separate; border-spacing: 0 8px;">
                    <thead style="background-color: #5a3ea1; color: white;">
                        <tr>
                            <th style="width: 30%; border-radius: 8px 0 0 8px;">Utilisateur</th>
                            <th style="width: 40%;">Email</th>
                            <th style="width: 30%; border-radius: 0 8px 8px 0;">Dernière activité</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($connexions as $connexion)
                        <tr class="align-middle" style="background-color: #faf9ff; box-shadow: 0 1px 4px rgba(111, 66, 193, 0.1); margin-bottom: 8px;">
                            <td class="fw-semibold text-truncate" style="max-width: 100%; border-left: 4px solid #6f42c1;">
                                @if($connexion->prenom || $connexion->nom)
                                    {{ $connexion->prenom ?? '' }} {{ $connexion->nom ?? '' }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-truncate" style="max-width: 100%;">{{ $connexion->email ?? '-' }}</td>
                            <td style="border-right: 4px solid #6f42c1;">
                                @if($connexion->last_activity)
                                    {{ $connexion->last_activity->format('d/m/Y H:i') }}
                                    <br>
                                    <small class="text-muted">{{ $connexion->last_activity->diffForHumans() }}</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
</div>

<style>
    .table-hover tbody tr:hover {
        background-color: #e6d9fc !important;
        cursor: pointer;
    }
    .fw-semibold {
        font-weight: 600 !important;
    }
    tbody tr:not(:last-child) {
        margin-bottom: 8px;
    }
</style>
@endsection
