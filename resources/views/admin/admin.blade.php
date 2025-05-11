@extends('layouts.app')

@section('content')
<div class="container">
    <div class="text-center my-5">
        <h1 class="display-4">Panneau d'administration</h1>
        <p class="lead">Bienvenue, {{ Auth::user()->prenom }} {{ Auth::user()->nom }} !</p>
    </div>

    <div class="card shadow border-0 rounded-4">
        <div class="card-body">
            <h4 class="mb-3">Statistiques ou options admin à afficher ici</h4>
            <p>Vous êtes connecté en tant qu'administrateur.</p>
            <ul>
                <li>Voir la liste des utilisateurs</li>
                <li>Gérer les permissions</li>
                <li>Consulter les logs</li>
                <!-- Ajoute ici d'autres liens ou composants utiles -->
