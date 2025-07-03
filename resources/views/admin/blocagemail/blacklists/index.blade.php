@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-violet">Blacklist</h1>
        <a href="{{ route('admin.blacklists.create') }}" 
        class="btn d-flex align-items-center gap-2" 
        style="background-color: #6f42c1; color: white; border-radius: 8px; padding: 10px 20px; font-weight: 600; transition: background-color 0.3s;">
            <i class="fas fa-plus"></i> Ajouter une entrée
        </a>

    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center" role="alert" style="gap: 10px;">
            <i class="fas fa-check-circle fs-5"></i>
            <div>{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @if($items->count())
    <div class="card shadow-sm border-0 rounded-4">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead class="bg-violet-light text-violet-dark">
                    <tr>
                        <th scope="col" style="width: 20%;">Type</th>
                        <th scope="col" style="width: 60%;">Valeur</th>
                        <th scope="col" class="text-end" style="width: 20%;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr class="blacklist-row @if($loop->first) newest-item @endif">
                        <td>
                            @if($item->type === 'email')
                                <span class="badge email-badge">Email</span>
                            @else
                                <span class="badge domain-badge">Domaine</span>
                            @endif
                        </td>
                        <td class="text-break fw-semibold text-violet-dark">{{ $item->value }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.blacklists.edit', $item) }}" class="btn btn-sm btn-outline-warning me-2" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </a>

                            <form action="{{ route('admin.blacklists.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Confirmer la suppression ?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $items->links() }}
    </div>

    @else
        <div class="alert alert-info text-center shadow-sm d-flex align-items-center justify-content-center gap-2">
            <i class="fas fa-info-circle fs-4"></i> Aucune entrée dans la blacklist.
        </div>
    @endif
</div>

<style>
    .text-violet {
        color: #6f42c1 !important;
    }
    .bg-violet-light {
        background-color: #d9c9f8 !important;
    }
    .text-violet-dark {
        color: #4b258d !important;
    }

    .badge {
        padding: 0.3em 0.8em;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        user-select: none;
    }
    .email-badge {
        background-color: #7e5bd8;
        color: white;
    }
    .domain-badge {
        background-color: #b496f5;
        color: #2d0e7c;
    }

    .blacklist-row {
        transition: background-color 0.3s ease;
    }
    .blacklist-row:hover {
        background-color: #f3eaff;
    }

      a.btn:hover {
    background-color: #582cae !important;
    color: white !important;
    text-decoration: none;
    }
    .newest-item {
        background-color: #e5dbf9;
        font-weight: 700;
        border-left: 5px solid #6f42c1;
    }

    .btn-outline-warning {
        color: #bb87ff;
        border-color: #bb87ff;
    }
    .btn-outline-warning:hover {
        background-color: #bb87ff;
        color: #fff;
        border-color: #bb87ff;
    }
    .btn-outline-danger:hover {
        background-color: #dc3545;
        color: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.9rem;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }
    }
</style>
@endsection
