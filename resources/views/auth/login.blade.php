@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4 bg-white">
                <div class="card-header text-center rounded-top-4" style="background: linear-gradient(90deg, #695F76 60%, #9280A3); color: white;">
                    <h2 class="my-3" style="letter-spacing:1px;font-weight:700">
                        Se connecter
                    </h2>
                </div>
                <div class="card-body px-4 py-4" style="background-color: #f6f0fa; border-radius: 0 0 2rem 2rem;">
                    @if (session('success'))
                        <div class="alert alert-success rounded-pill text-center py-2 mb-4" style="font-size:1.07rem;">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger rounded-pill text-center py-2 mb-4" style="font-size:1.07rem;">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-bold" style="color:#695F76;">Email</label>
                            <input type="email" name="email" id="email"
                                   class="form-control form-control-lg rounded-3"
                                   style="font-size:1.09rem;" required autofocus>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label fw-bold" style="color:#695F76;">Mot de passe</label>
                            <input type="password" name="password" id="password"
                                   class="form-control form-control-lg rounded-3"
                                   style="font-size:1.09rem;" required>
                        </div>

                        <div class="mb-3 text-center">
                            <button type="submit" class="btn btn-lg px-5 rounded-pill shadow-sm"
                                    style="background: linear-gradient(90deg, #9280A3, #695F76); color: #fff; font-weight:600;letter-spacing:1px;">
                                <i class="fas fa-sign-in-alt me-1"></i>Se connecter
                            </button>
                        </div>
                       <div class="mb-3 text-center">
                            <a href="{{ route('password.forgot') }}" class="fw-bold" style="color:#9280A3;">
                                <i class="fas fa-question-circle me-1"></i>Mot de passe oublié ?
                            </a>
                        </div>
                    </form>

                    <p class="text-center mt-4" style="color: #695F76; font-size:1.1rem;">
                        Pas encore de compte ?
                        <a href="{{ route('register') }}" style="color: #695F76; font-weight: bold;">S'inscrire</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
