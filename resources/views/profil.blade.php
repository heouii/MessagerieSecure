@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header d-flex justify-content-between align-items-center" style="background-color: #BAA8D3; color: white;">
                    <h4 class="mb-0">Mon Profil</h4>
                        <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm" style="border-radius: 6px;">
                            <i class="fas fa-arrow-left"></i> Retour au Dashboard
                        </a>
                </div>
                <div class="card-body" style="background-color: #f9f9f9;">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('password_error'))
                        <div class="alert alert-danger">{{ session('password_error') }}</div>
                    @endif
                    @if(session('password_success'))
                        <div class="alert alert-success">{{ session('password_success') }}</div>
                    @endif
                    @if(session('security_error'))
                        <div class="alert alert-danger">{{ session('security_error') }}</div>
                    @endif
                    @if(session('security_success'))
                        <div class="alert alert-success">{{ session('security_success') }}</div>
                    @endif

                    <form id="profilForm" action="{{ route('profil.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="prenom" name="prenom" value="{{ old('prenom', $user->prenom) }}" required style="border-radius: 8px;">
                        </div>
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" class="form-control" id="nom" name="nom" value="{{ old('nom', $user->nom) }}" required style="border-radius: 8px;">
                        </div>
                        <div class="mb-3">
                            <label for="tel" class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" id="tel" name="tel" value="{{ old('tel', $user->tel) }}" required pattern="\d{10}" title="Le numéro de téléphone doit être composé de 10 chiffres" style="border-radius: 8px;">
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3">Changer le mot de passe</h5>
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Ancien mot de passe</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" style="border-radius: 8px;">
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password" style="border-radius: 8px;">
                        </div>
                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label">Confirmer le nouveau mot de passe</label>
                            <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" autocomplete="new-password" style="border-radius: 8px;">
                        </div>

                        <hr class="my-4">
                        <h5>Question de sécurité</h5>
                        <div class="mb-3">
                            <label for="security_question" class="form-label">Choisissez votre question de sécurité</label>
                            <select name="security_question" id="security_question" class="form-control" required>
                                <option value="">-- Choisissez une question --</option>
                                <option value="Quel est le nom de votre premier animal ?" {{ old('security_question', $user->security_question) == 'Quel est le nom de votre premier animal ?' ? 'selected' : '' }}>Quel est le nom de votre premier animal ?</option>
                                <option value="Quel est le nom de jeune fille de votre mère ?" {{ old('security_question', $user->security_question) == 'Quel est le nom de jeune fille de votre mère ?' ? 'selected' : '' }}>Quel est le nom de jeune fille de votre mère ?</option>
                                <option value="Dans quelle ville êtes-vous né ?" {{ old('security_question', $user->security_question) == 'Dans quelle ville êtes-vous né ?' ? 'selected' : '' }}>Dans quelle ville êtes-vous né ?</option>
                                <option value="Quel était votre surnom d’enfance ?" {{ old('security_question', $user->security_question) == 'Quel était votre surnom d’enfance ?' ? 'selected' : '' }}>Quel était votre surnom d’enfance ?</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="security_answer" class="form-label">Votre réponse</label>
                            <input type="text" name="security_answer" id="security_answer" class="form-control" autocomplete="off" placeholder="Laisser vide pour ne pas changer">
                            <small class="form-text text-muted">Seule la dernière réponse sera conservée.</small>
                        </div>

                        <button type="button" id="openSecurityModal" class="btn" style="background-color: #BAA8D3; color: white; border-radius: 8px; width: 100%;">
                            Mettre à jour
                        </button>
                        <button type="submit" id="realSecuritySubmit" class="d-none"></button>

                    </form>

                    <div class="modal fade" id="securityPasswordModal" tabindex="-1" aria-labelledby="securityPasswordModalLabel" aria-hidden="true">
                      <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                          <div class="modal-header">
                            <h5 class="modal-title" id="securityPasswordModalLabel">Valider avec votre mot de passe</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                          </div>
                          <div class="modal-body">
                            <div class="mb-3">
                                <label for="security_password_modal" class="form-label">Mot de passe</label>
                                <input type="password" name="security_password_modal" id="security_password_modal" class="form-control" required autocomplete="off">
                            </div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="button" class="btn btn-primary" id="submitSecurityFormBtn">Valider</button>
                          </div>
                        </div>
                      </div>
                    </div>

                    @if($sessions->count() > 0)
                        <hr class="my-4">
                        <h5 class="mb-3">Sessions actives</h5>
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>IP</th>
                                    <th>User-Agent</th>
                                    <th>Dernière activité</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($sessions as $session)
                                <tr @if(session()->getId() === $session->id) style="background:#e6e6fa" @endif>
                                    <td>{{ $session->ip_address }}</td>
                                    <td style="max-width:200px;overflow:auto">{{ \Illuminate\Support\Str::limit($session->user_agent, 60) }}</td>
                                    <td>{{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}</td>
                                    <td>
                                        @if(session()->getId() !== $session->id)
                                        <form method="POST" action="{{ route('profil.sessions.destroy', $session->id) }}" style="display:inline;">
                                            @csrf
                                            <button type="submit" class="btn btn-danger btn-sm">Déconnecter</button>
                                        </form>
                                        @else
                                            <span class="text-success">Cette session</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const openModalBtn = document.getElementById('openSecurityModal');
    const submitModalBtn = document.getElementById('submitSecurityFormBtn');
    const realSubmitBtn = document.getElementById('realSecuritySubmit');
    const securityPasswordInput = document.getElementById('security_password_modal');

    openModalBtn.addEventListener('click', function (e) {
        e.preventDefault();
        var myModal = new bootstrap.Modal(document.getElementById('securityPasswordModal'));
        myModal.show();
    });

    submitModalBtn.addEventListener('click', function () {
        let form = openModalBtn.closest('form');
        let existing = document.getElementById('security_password');
        if(!existing) {
            let hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'security_password';
            hidden.id = 'security_password';
            form.appendChild(hidden);
            existing = hidden;
        }
        existing.value = securityPasswordInput.value;
        realSubmitBtn.click();
    });
});
</script>
@endsection
