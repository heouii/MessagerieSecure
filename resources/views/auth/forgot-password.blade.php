@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow-lg border-0 rounded-4 bg-white">
                <div class="card-header text-center" style="background: linear-gradient(90deg, #695F76 60%, #9280A3); color: white;">
                    <h2 class="my-3">Réinitialiser le mot de passe</h2>
                </div>
                <div class="card-body" style="background: #f6f0fa;">
                    @if (session('recovery_error'))
                        <div class="alert alert-danger rounded-pill text-center mb-4">
                            {{ session('recovery_error') }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('password.forgot.post') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold" style="color:#695F76;">Email</label>
                            <input type="email" name="email" id="email" class="form-control form-control-lg rounded-3" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-bold" style="color:#695F76;">Nouveau mot de passe</label>
                            <input type="password" name="new_password" id="new_password" class="form-control form-control-lg rounded-3" required>
                        </div>
                        <div class="mb-4">
                            <label for="new_password_confirmation" class="form-label fw-bold" style="color:#695F76;">Confirmer le mot de passe</label>
                            <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control form-control-lg rounded-3" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" class="btn btn-lg rounded-pill shadow-sm px-4"
                                style="background: linear-gradient(90deg, #9280A3, #695F76); color: #fff; font-weight:600;">Valider</button>
                        </div>
                    </form>
                    <div class="text-center mt-4">
                        <a href="{{ route('login') }}" style="color: #695F76; font-weight: bold;">Retour à la connexion</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
