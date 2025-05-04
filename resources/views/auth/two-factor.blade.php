@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header text-center" style="background-color: #695F76; color: white;">
                    <h2 class="my-3">Vérification 2MFA</h2>
                </div>

                <div class="card-body" style="background-color: #BAA8D3;">
                    <p class="text-center">Scannez ce code QR avec une application comme Google Authenticator :</p>
                    <div class="text-center mb-4">
                        <!-- Afficher le QR code ici -->
                        <img src="{{ $QRUrl }}" alt="QR Code" width="200" height="200">
                    </div>

                    <form method="POST" action="{{ route('two-factor.verify') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="code" class="form-label">Code de vérification</label>
                            <input type="text" name="code" id="code" class="form-control" required>
                        </div>

                        <div class="mb-3 text-center">
                            <button type="submit" class="btn btn-light btn-lg" style="background-color: #9280A3; color: white; border: none; border-radius: 20px;">Vérifier</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
