@auth
<nav class="navbar navbar-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <i class="fas fa-envelope"></i> Missive
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline-light">
                <i class="fas fa-sign-out-alt"></i> Déconnexion
            </button>
        </form>
    </div>
</nav>
@else
<nav class="navbar navbar-expand-lg navbar-light">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ url('/') }}">
            <h1 class="h3 mb-0">Missive</h1>
        </a>
        <div class="d-flex">
            <a href="{{ route('login') }}" class="btn btn-outline-light me-2">Se connecter</a>
            <a href="{{ route('register') }}" class="btn btn-light">S'inscrire</a>
        </div>
    </div>
</nav>
@endauth