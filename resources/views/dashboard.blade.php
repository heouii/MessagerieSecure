@extends('layouts.app')

@section('content')
<div class="dashboard-wrapper">

    <h1 class="dashboard-title">
        Bienvenue, {{ auth()->user()->prenom }} {{ auth()->user()->nom }}
    </h1>

    <div class="dashboard-welcome">
        Heure actuelle : <span id="clock"></span>
    </div>

    <div class="dashboard-grid">
        <div class="tile">
            <i class="fas fa-envelope-open-text"></i>
            <h3>Messagerie Sécurisée</h3>
            <p>Envoyez et recevez vos messages cryptés.</p>
            <a href="{{ route('mailgun.index') }}">Ouvrir</a>
        </div>
        <div class="tile">
            <i class="fas fa-user-circle"></i>
            <h3>Profil</h3>
            <p>Mettez à jour vos informations personnelles.</p>
            <a href="{{ route('profil.show') }}">Gérer</a>
        </div>
        <div class="tile">
            <i class="fas fa-cogs"></i>
            <h3>Paramètres</h3>
            <p>Configurez votre compte et votre sécurité.</p>
            <a href="{{ route('parametres') }}">Paramètres</a>
        </div>
    </div>
</div>

<style>

body {
    background: #ece8f4;
}


.dashboard-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 3rem 1rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.dashboard-title {
    font-size: 2.8rem;
    font-weight: 700;
    color: #333;
    margin-bottom: 0.5rem;
}

.dashboard-welcome {
    font-size: 1rem;
    color: #555;
    margin-bottom: 2rem;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    width: 100%;
}

.tile {
    background: #927ca6;
    color: #fff;
    border-radius: 0.75rem;
    padding: 2rem 1.5rem;
    text-align: center;
    position: relative;
    transition: transform .3s, box-shadow .3s;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    min-height: 220px;
}
.tile:hover {
    transform: translateY(-4px) scale(1.02);
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
}
.tile i {
    font-size: 2rem;
    margin-bottom: 0.75rem;
}
.tile h3 {
    font-size: 1.3rem;
    margin-bottom: 0.5rem;
    font-weight: 600;
}
.tile p {
    font-size: 0.95rem;
    margin-bottom: 1rem;
}
.tile a {
    display: inline-block;
    padding: 0.4rem 1rem;
    background: rgba(255,255,255,0.2);
    color: #fff;
    border-radius: .375rem;
    font-weight: 500;
    text-decoration: none;
    transition: background .2s;
}
.tile a:hover {
    background: rgba(255,255,255,0.3);
}

@media (min-width: 992px) {
    .tile {
        min-height: 250px;
    }
}
</style>

<script>
function updateClock() {
    const now = new Date();
    const clock = document.getElementById('clock');
    clock.textContent = now.toLocaleTimeString();
}
setInterval(updateClock, 1000);
updateClock();
</script>
@endsection
