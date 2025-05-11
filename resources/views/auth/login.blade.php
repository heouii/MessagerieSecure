@extends('layouts.app')

@section('content')
<br>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header text-center" style="background-color: #695F76; color: white;">
                    <h2 class="my-3">Se connecter</h2>
                </div>

                <div class="card-body" style="background-color: #BAA8D3;">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <strong>Erreur :</strong> {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>

                        <div class="mb-3 text-center">
                            <button type="submit" class="btn btn-light btn-lg" style="background-color: #9280A3; color: white; border: none; border-radius: 20px;">Se connecter</button>
                        </div>
                    </form>

                    <p class="text-center mt-4" style="color: #695F76;">Pas encore de compte ? 
                        <a href="{{ route('register') }}" style="color: #695F76; font-weight: bold;">S'inscrire</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
