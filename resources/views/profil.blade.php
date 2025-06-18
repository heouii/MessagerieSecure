@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header" style="background-color: #BAA8D3; color: white;">
                    <h4>Mon Profil</h4>
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

                    <form action="{{ route('profil.update') }}" method="POST">
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
                        <button type="submit" class="btn" style="background-color: #BAA8D3; color: white; border-radius: 8px; width: 100%;">Mettre à jour</button>
                    </form>

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

                    <hr class="my-4">
                    <h5 class="mb-3">Authentification à deux facteurs (2FA)</h5>
                    @if($user->hasTwoFactorEnabled())
                        <form method="POST" action="{{ route('profil.2fa.disable') }}">
                            @csrf
                            <div class="mb-2">
                                <label for="password_2fa" class="form-label">Mot de passe actuel pour désactiver le 2FA :</label>
                                <input type="password" name="password_2fa" class="form-control" required>
                            </div>
                            <button type="submit" class="btn" style="background-color: #BAA8D3; color: white; border-radius: 8px; width: 100%;">Désactiver le 2FA</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('profil.2fa.enable') }}">
                            @csrf
                            <div class="mb-2">
                                <label for="password_2fa" class="form-label">Mot de passe actuel pour activer le 2FA :</label>
                                <input type="password" name="password_2fa" class="form-control" required>
                            </div>
                            <button type="submit" class="btn" style="background-color: #BAA8D3; color: white; border-radius: 8px; width: 100%;">Activer le 2FA</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
