@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4 bg-white">
                <div class="card-header text-center rounded-top-4" style="background: linear-gradient(90deg, #695F76 60%, #9280A3); color: white;">
                    <h2 class="my-3" style="letter-spacing:1px;font-weight:700">Créer un compte</h2>
                </div>
                <div class="card-body px-4 py-4" style="background-color: #f6f0fa; border-radius: 0 0 2rem 2rem;">
                    @if ($errors->any())
                        <div class="alert alert-danger rounded-pill text-center py-2 mb-4" style="font-size:1.1rem;">
                            <ul class="mb-0" style="list-style:none;padding:0;">
                                @foreach ($errors->all() as $error)
                                    <li class="py-1">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('register') }}" autocomplete="off">
                        @csrf

                        <div class="mb-3">
                            <label for="prenom" class="form-label fw-bold" style="color:#695F76;">Prénom</label>
                            <input type="text" name="prenom" id="prenom" class="form-control form-control-lg rounded-3"
                                   value="{{ old('prenom') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="nom" class="form-label fw-bold" style="color:#695F76;">Nom</label>
                            <input type="text" name="nom" id="nom" class="form-control form-control-lg rounded-3"
                                   value="{{ old('nom') }}" required>
                        </div>
                        <div class="mb-3">
                            <label for="tel" class="form-label fw-bold" style="color:#695F76;">Téléphone</label>
                            <input type="tel" name="tel" id="tel" class="form-control form-control-lg rounded-3"
                                   value="{{ old('tel') }}" required pattern="\d{10}" placeholder="0600000000">
                        </div>

                        {{-- Question secrète --}}
                        <div class="mb-3">
                            <label for="security_question" class="form-label fw-bold" style="color:#695F76;">
                                Question de sécurité
                            </label>
                            <select name="security_question" id="security_question" class="form-select form-select-lg rounded-3"
                                    style="background:#f9f8fc;border:1px solid #9280A3;color:#695F76;font-size:1.07rem;" required>
                                <option value="">-- Choisissez une question --</option>
                                <option value="Quel est le nom de votre premier animal ?" {{ old('security_question') == 'Quel est le nom de votre premier animal ?' ? 'selected' : '' }}>
                                    Quel est le nom de votre premier animal ?
                                </option>
                                <option value="Quel est le nom de jeune fille de votre mère ?" {{ old('security_question') == 'Quel est le nom de jeune fille de votre mère ?' ? 'selected' : '' }}>
                                    Quel est le nom de jeune fille de votre mère ?
                                </option>
                                <option value="Dans quelle ville êtes-vous né ?" {{ old('security_question') == 'Dans quelle ville êtes-vous né ?' ? 'selected' : '' }}>
                                    Dans quelle ville êtes-vous né ?
                                </option>
                                <option value="Quel était votre surnom d’enfance ?" {{ old('security_question') == 'Quel était votre surnom d’enfance ?' ? 'selected' : '' }}>
                                    Quel était votre surnom d’enfance ?
                                </option>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label for="security_answer" class="form-label fw-bold" style="color:#695F76;">Votre réponse</label>
                            <input type="text" name="security_answer" id="security_answer" class="form-control form-control-lg rounded-3"
                                   required autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-bold" style="color:#695F76;">Mot de passe</label>
                            <input type="password" name="password" id="password" class="form-control form-control-lg rounded-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label fw-bold" style="color:#695F76;">Confirmer le mot de passe</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control form-control-lg rounded-3" required>
                        </div>
                        <div class="mb-3 text-center">
                            <button type="submit" class="btn btn-lg px-5 rounded-pill shadow-sm"
                                style="background: linear-gradient(90deg, #9280A3, #695F76); color: #fff; font-weight:600;letter-spacing:1px;">
                                <i class="fas fa-user-plus me-1"></i> S'inscrire
                            </button>
                        </div>
                    </form>

                    <p class="text-center mt-4" style="color: #695F76; font-size:1.1rem;">
                        Vous avez déjà un compte ?
                        <a href="{{ route('login') }}" style="color: #695F76; font-weight: bold;">Se connecter</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
