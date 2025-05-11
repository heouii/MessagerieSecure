@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header text-center" style="background-color: #695F76; color: white;">
                    <h2 class="my-3">Vérification 2FA</h2>
                </div>

                <div class="card-body" style="background-color: #BAA8D3;">
                    <p class="text-center">Scannez ce code QR avec une application comme Google Authenticator :</p>
                    
                    <div class="text-center mb-4">
                        <img src="{{ $qrCodeImage }}" alt="QR Code" width="200" height="200">
                    </div>


                    <form method="POST" action="{{ route('two-factor.verify') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="code" class="form-label">Code de vérification</label>
                            <input type="text" name="code" id="code" class="form-control" required autofocus>
                        </div>

                        <div class="mb-3 text-center">
                            <button type="submit" class="btn btn-light btn-lg" style="background-color: #9280A3; color: white; border: none; border-radius: 20px;">Vérifier</button>
                        </div>

                        @if ($errors->has('code'))
                            <div class="text-danger text-center">
                                {{ $errors->first('code') }}
                            </div>
                        @endif

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
