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
                    <!-- Afficher un message de succès s'il y en a -->
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
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


                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="email" name="email" value="{{ old('email', explode('@', $user->email)[0]) }}" required style="border-radius: 8px;">
                                <span class="input-group-text" style="border-radius: 8px;">@missive-si.fr</span>
                            </div>
                        </div>

                        <button type="submit" class="btn" style="background-color: #BAA8D3; color: white; border-radius: 8px; width: 100%;">Mettre à jour</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
