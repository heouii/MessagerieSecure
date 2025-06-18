@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-header text-center" style="background-color: #695F76; color: white;">
                    <h2 class="my-3">Validation 2FA</h2>
                </div>
                <div class="card-body" style="background-color: #BAA8D3;">
                        @if ($showQr)
                        <p class="text-center">
                             Scanne le QR code:
                        </p>
                        <div class="text-center mb-4">
                            {!! $qrCode !!}
                        </div>
                        @endif


                    <form method="POST" action="{{ route('two-factor.verify') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label">Code à 6 chiffres</label>
                            <input type="text" name="code" id="code" class="form-control" maxlength="6" required>
                        </div>
                        <div class="mb-3 text-center">
                            <button type="submit" class="btn btn-light btn-lg" style="background-color: #9280A3; color: white; border: none; border-radius: 20px;">Valider</button>
                        </div>
                        @if ($errors->any())
                            <div class="alert alert-danger text-center">{{ $errors->first() }}</div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
