@extends('layouts.app')

@section('content')
    <br>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-header text-center" style="background-color: #695F76; color: white;">
                        <h2 class="my-3">Vérification 2FA</h2>
                    </div>

                    <div class="card-body" style="background-color: #BAA8D3;">
                        <p class="text-center">Scannez le QR code avec votre application d'authentification pour compléter la vérification.</p>

                        @if(isset($qrCodeImage))
                            <div class="text-center">
                                <img src="{{ $qrCodeImage }}" alt="QR Code" class="img-fluid" />
                            </div>
                        @else
                            <p class="text-center text-danger">Le QR Code ne s'est pas généré correctement. Veuillez réessayer.</p>
                        @endif

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

                        <p class="text-center mt-4" style="color: #695F76;">Si vous n'avez pas reçu de code, <a href="{{ route('two-factor.resend') }}" style="font-weight: bold;">renvoyer le code</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
