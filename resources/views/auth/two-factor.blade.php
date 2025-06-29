@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow-lg border-0 rounded-4 bg-white">
                <div class="card-header text-center rounded-top-4" style="background: linear-gradient(90deg, #695F76 60%, #9280A3); color: white;">
                    <h2 class="my-3" style="letter-spacing:1px;font-weight:700">Validation 2FA</h2>
                </div>
                <div class="card-body px-4 py-4" style="background-color: #f6f0fa; border-radius: 0 0 2rem 2rem;">
                    {{-- Message QR Code --}}
                    @if ($showQr)
                        <div class="mb-4">
                            <p class="text-center fs-5 mb-2" style="color:#695F76;">
                                <i class="fas fa-qrcode me-2"></i>Scanne le QR code avec Google Authenticator :
                            </p>
                            <div class="d-flex justify-content-center my-3">
                                <div style="background: white; border-radius: 2rem; box-shadow:0 4px 12px #9280a315; padding:20px;">
                                    {!! $qrCode !!}
                                </div>
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('two-factor.verify') }}" class="mb-2">
                        @csrf
                        <div class="mb-3">
                            <label for="code" class="form-label fw-bold" style="color:#695F76;">Code à 6 chiffres</label>
                            <input type="text" name="code" id="code" class="form-control form-control-lg rounded-3 text-center fs-5"
                                   maxlength="6" required autocomplete="one-time-code" style="letter-spacing:2px;">
                        </div>
                        @if ($errors->any())
                            <div class="alert alert-danger text-center rounded-pill py-2 my-3">{{ $errors->first() }}</div>
                        @endif
                        <div class="mb-3 text-center">
                            <button type="submit" class="btn btn-lg px-5 rounded-pill shadow-sm"
                                style="background: linear-gradient(90deg, #9280A3, #695F76); color: #fff; font-weight:600;">
                                <i class="fas fa-check-circle me-1"></i>Valider
                            </button>
                        </div>
                    </form>

                    <hr style="opacity:0.15">

                    {{-- Bouton pour réinitialiser le MFA --}}
                    <div class="text-center mb-2 mt-4">
                        <button type="button" class="btn btn-warning rounded-pill shadow-sm fw-bold px-4"
                                style="background: linear-gradient(90deg, #9280A3, #9280A3); color: #695F76;"
                                data-bs-toggle="modal" data-bs-target="#reset2faModal">
                            <i class="fas fa-sync-alt me-2"></i>Réinitialiser le MFA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal Bootstrap --}}
<div class="modal fade" id="reset2faModal" tabindex="-1" aria-labelledby="reset2faModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('two-factor.reset') }}">
        @csrf
        <div class="modal-content rounded-4">
            <div class="modal-header rounded-top-4" style="background: linear-gradient(90deg,#FFD600,#FFAF00);">
                <h5 class="modal-title" id="reset2faModalLabel" style="color:#695F76;font-weight:700;">
                    <i class="fas fa-shield-alt me-2"></i>Réinitialiser le MFA
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body" style="background: #FAF6E3;">
                <div class="mb-2">
                    <div class="fw-bold mb-2" style="color:#9280A3;">
                        <i class="fas fa-question-circle me-1"></i>
                        {{ $user->security_question ?? 'Aucune question de sécurité enregistrée.' }}
                    </div>
                    <input type="text" name="security_answer" class="form-control rounded-pill border-0 shadow-sm"
                           placeholder="Votre réponse" required>
                </div>
                @if (session('reset2fa_error'))
                    <div class="alert alert-danger text-center mt-3 mb-0 rounded-pill">{{ session('reset2fa_error') }}</div>
                @endif
                @if (session('reset2fa_success'))
                    <div class="alert alert-success text-center mt-3 mb-0 rounded-pill">{{ session('reset2fa_success') }}</div>
                @endif
            </div>
            <div class="modal-footer" style="background: #FAF6E3;">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold" style="color:#695F76;">
                    <i class="fas fa-sync-alt me-1"></i>Réinitialiser
                </button>
            </div>
        </div>
    </form>
  </div>
</div>

{{-- Auto-open modal on error --}}
@if (session('reset2fa_error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var modal = new bootstrap.Modal(document.getElementById('reset2faModal'));
            modal.show();
        });
    </script>
@endif

@endsection
