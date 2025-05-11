@extends('layouts.app')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg">
                <div class="card-header" style="background-color: #BAA8D3; color: white;">
                    <h4>Paramètres du Compte</h4>
                </div>

                <div class="card-body" style="background-color: #f9f9f9;">
                    <!-- Afficher un message de succès s'il y en a -->
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <!-- Afficher un message d'erreur s'il y en a -->
                    @if($errors->any())
                        <div class="alert alert-danger">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    @endif

                    <!-- RGPD Section -->
                    <div class="mb-4">
                        <h5>Règlement Général sur la Protection des Données (RGPD)</h5>
                        <p>Conformément au RGPD, vous avez le droit d'accéder, de modifier ou de supprimer vos données personnelles. Nous nous engageons à protéger vos informations et à respecter votre vie privée.</p>
                        <p>Pour en savoir plus sur notre politique de confidentialité, veuillez consulter notre <a href="#">politique de confidentialité</a>.</p>
                    </div>

                    <!-- Suppression de compte -->
                    <div class="mb-4">
                        <h5>Supprimer mon compte</h5>
                        <p>Si vous souhaitez supprimer définitivement votre compte, toutes vos données seront effacées de notre système. Cette action est irréversible. Cependant, vous avez 30 jours pour annuler cette suppression.</p>

                        <form action="{{ route('parametre.delete') }}" method="POST">
                            @csrf
                            @method('POST')

                            <div class="mb-3">
                                <label for="password" class="form-label">Confirmez votre mot de passe</label>
                                <input type="password" class="form-control" id="password" name="password" required style="border-radius: 8px;">
                            </div>

                            <button type="submit" class="btn btn-danger" style="width: 100%; border-radius: 8px;">
                                Supprimer mon compte
                            </button>
                        </form>
                    </div>

                    <!-- Annuler la suppression -->
                    @if(Auth::user()->deleted_at)
                        <div class="mb-4">
                            <h5>Annuler la suppression de mon compte</h5>
                            <p>Votre compte est actuellement en attente de suppression. Vous avez encore 30 jours pour annuler cette suppression. Si vous souhaitez annuler la suppression, cliquez sur le bouton ci-dessous.</p>

                            <form action="{{ route('parametre.cancelDeletion') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success" style="width: 100%; border-radius: 8px;">
                                    Annuler la suppression
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
