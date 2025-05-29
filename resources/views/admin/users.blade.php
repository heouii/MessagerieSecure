@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Liste des utilisateurs</h1>

    <div class="mb-3">
        <input type="text" id="searchUser" class="form-control" placeholder="Rechercher un utilisateur...">
    </div>

    <div class="table-responsive" style="max-height: 500px;">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Date d'inscription</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
                @foreach ($users as $user)
                <tr>
                    <td>{{ $user->prenom }} {{ $user->nom }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->tel }}</td>
                    <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>

                    <td>
                        @if($user->is_blocked)
                            <span class="badge bg-danger">Bloqué</span><br>
                            <small>
                                @if($user->blocked_until)
                                    jusqu'au {{ $user->blocked_until->format('d/m/Y H:i') }}
                                @else
                                    indéfini
                                @endif
                            </small>
                        @else
                            <span class="badge bg-success">Actif</span>
                        @endif
                    </td>

                    <td>
                        @if($user->is_blocked)
                            <form method="POST" action="{{ route('admin.users.block', $user->id) }}" style="display:inline;">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="block_duration" value="0"> {{-- 0 pour débloquer --}}
                                <button type="submit" class="btn btn-sm btn-warning">Débloquer</button>
                            </form>
                        @else
                            <a href="#" 
                               class="btn btn-sm btn-danger" 
                               data-bs-toggle="modal" 
                               data-bs-target="#blockUserModal"
                               data-userid="{{ $user->id }}"
                               data-username="{{ $user->prenom }} {{ $user->nom }}">
                               Bloquer
                            </a>
                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-sm btn-info">Voir</a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal blocage -->
<div class="modal fade" id="blockUserModal" tabindex="-1" aria-labelledby="blockUserModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" id="blockUserForm" action="">
        @csrf
        @method('PATCH')
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="blockUserModalLabel">Bloquer l'utilisateur</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
          </div>
          <div class="modal-body">
            <p>Bloquer <strong id="modalUserName"></strong> pour :</p>
            <select class="form-select" name="block_duration" required>
                <option value="" disabled selected>Durée du blocage</option>
                <option value="3">3 jours</option>
                <option value="5">5 jours</option>
                <option value="7">7 jours</option>
                <option value="30">30 jours</option>
                <option value="0">Indéfini</option>
            </select>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
            <button type="submit" class="btn btn-warning">Bloquer</button>
          </div>
        </div>
    </form>
  </div>
</div>

<script>
    // Filtrer la liste
    document.getElementById('searchUser').addEventListener('input', function() {
        const search = this.value.toLowerCase();
        const rows = document.querySelectorAll('#userTableBody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(search) ? '' : 'none';
        });
    });

    // Préparer modal blocage
    const blockUserModal = document.getElementById('blockUserModal');
    blockUserModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const userId = button.getAttribute('data-userid');
        const userName = button.getAttribute('data-username');
        const form = document.getElementById('blockUserForm');
        const modalUserName = document.getElementById('modalUserName');

        modalUserName.textContent = userName;
        form.action = `/admin/users/${userId}/block`;
    });
</script>

<style>
    thead.table-light {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    .table-responsive {
        max-height: 500px;
        overflow-y: auto;
        scrollbar-width: thin;
        scrollbar-color: #BAA8D3 transparent;
    }
    .table-responsive::-webkit-scrollbar {
        width: 8px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background-color: #BAA8D3;
        border-radius: 4px;
    }
</style>
@endsection
