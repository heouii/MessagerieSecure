@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-primary">Blacklist</h1>
        <a href="{{ route('admin.blacklists.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> Ajouter une entrée
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
        </div>
    @endif

    @if($items->count())
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-striped align-middle mb-0">
                <thead class="table-light text-secondary">
                    <tr>
                        <th scope="col">Type</th>
                        <th scope="col">Valeur</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td class="text-capitalize">{{ $item->type }}</td>
                        <td>{{ $item->value }}</td>
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

    <div class="mt-3 d-flex justify-content-center">
        {{ $items->links() }}
    </div>

    @else
        <div class="alert alert-info text-center shadow-sm">
            <i class="fas fa-info-circle me-2"></i> Aucune entrée dans la blacklist.
        </div>
    @endif
</div>
@endsection
