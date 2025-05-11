@extends('layouts.app')

@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header text-center" style="background-color: #695F76; color: white;">
                    <h2 class="my-3">S'Inscrire</h2>
                </div>
                <div class="card-body" style="background-color: #BAA8D3;">
                    <form method="POST" action="{{ route('register') }}">

                        @csrf

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="prenom" class="form-label">Prénom</label>
                            <input type="text" name="prenom" id="prenom" class="form-control" value="{{ old('prenom') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom</label>
                            <input type="text" name="nom" id="nom" class="form-control" value="{{ old('nom') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="tel" class="form-label">Téléphone</label>
                           <input type="tel" name="tel" id="tel" class="form-control" value="{{ old('tel') }}" required placeholder="00-00-00-00-00">
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmer le mot de passe</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                        </div>

                        <div class="mb-3 text-center">
                            <button type="submit" class="btn btn-light btn-lg" style="background-color: #9280A3; color: white; border: none; border-radius: 20px;">S'inscrire</button>
                        </div>
                    </form>

                    <p class="text-center mt-4" style="color: #695F76;">Vous avez déjà un compte ? <a href="{{ route('login') }}" style="color: #695F76; font-weight: bold;">Se connecter</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
