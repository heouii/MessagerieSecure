@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="mb-4 fw-bold">Tableau de bord Administrateur</h1>

    <div class="row g-3 mb-4">
        @php
            $stats = [
                ['label' => 'Utilisateurs inscrits', 'value' => $totalUsers, 'icon' => 'fas fa-users', 'color' => '#B39DDB', 'link' => route('admin.users')],
                ['label' => 'Admins', 'value' => $totalAdmins, 'icon' => 'fas fa-user-shield', 'color' => '#9575CD'],
                ['label' => 'Messages envoyés', 'value' => $totalMessages, 'icon' => 'fas fa-envelope', 'color' => '#7986CB'],
                ['label' => 'Utilisateurs bloqués', 'value' => $blockedUsersCount, 'icon' => 'fas fa-user-lock', 'color' => '#CE93D8', 'link' => route('admin.users')],
            ];
        @endphp

        @foreach ($stats as $stat)
        <div class="col-md-3">
            @if (isset($stat['link']))
                <a href="{{ $stat['link'] }}" class="text-decoration-none">
            @endif
            <div class="card shadow-sm border-0 text-white h-100" style="background: {{ $stat['color'] }};">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="fs-2"><i class="{{ $stat['icon'] }}"></i></div>
                    <div>
                        <h5 class="mb-1">{{ $stat['label'] }}</h5>
                        <p class="fs-3 fw-bold m-0">{{ $stat['value'] }}</p>
                    </div>
                </div>
            </div>
            @if (isset($stat['link']))
                </a>
            @endif
        </div>
        @endforeach
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold bg-light">Inscriptions récentes</div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartRegistrations" style="max-height: 250px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold bg-light">Utilisateurs actifs vs bloqués</div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartUserStatus" style="max-height: 250px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold bg-light">Spams détectés par type</div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartSpams" style="max-height: 250px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header fw-bold bg-light">Comptes supprimés sur 7 jours</div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartDeleted" style="max-height: 250px; width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
            <span>Derniers inscrits</span>
            <input type="text" id="searchUser" class="form-control form-control-sm w-auto" placeholder="Rechercher...">
        </div>
        <div class="table-responsive" style="max-height: 400px;">
            <table class="table table-hover mb-0">
                <thead class="table-light sticky-top">
                    <tr>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Date d'inscription</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    @foreach ($recentUsers as $user)
                    <tr>
                        <td>{{ $user->prenom }} {{ $user->nom }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->tel }}</td>
                        <td>{{ $user->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const labels = {!! json_encode($chartLabels) !!};
    const data = {!! json_encode($chartData) !!};

    new Chart(document.getElementById('chartRegistrations'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Inscriptions par jour',
                data: data,
                borderColor: '#7E57C2',
                backgroundColor: 'rgba(126,87,194,0.3)',
                tension: 0.4,
                fill: true,
                pointRadius: 4
            }]
        },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('chartUserStatus'), {
        type: 'doughnut',
        data: {
            labels: ['Utilisateurs actifs', 'Utilisateurs bloqués'],
            datasets: [{
                data: [{{ $totalUsers - $blockedUsersCount }}, {{ $blockedUsersCount }}],
                backgroundColor: ['#9575CD', '#CE93D8'],
                hoverOffset: 20,
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            plugins: {
                legend: { position: 'bottom' }
            },
            cutout: '70%'
        }
    });

    new Chart(document.getElementById('chartSpams'), {
        type: 'radar',
        data: {
            labels: ['Phishing', 'Publicité', 'Malware', 'Autres'],
            datasets: [{
                label: 'Spams détectés',
                backgroundColor: 'rgba(206,147,216,0.2)',
                borderColor: '#CE93D8',
                pointBackgroundColor: '#CE93D8'
            }]
        },
        options: { scales: { r: { beginAtZero: true } } }
    });

    new Chart(document.getElementById('chartDeleted'), {
        type: 'bar',
        data: {
            labels: ['Jour 1', 'Jour 2', 'Jour 3', 'Jour 4', 'Jour 5', 'Jour 6', 'Jour 7'],
            datasets: [{
                label: 'Comptes supprimés',
                backgroundColor: '#B39DDB'
            }]
        },
        options: { scales: { y: { beginAtZero: true } } }
    });

    document.getElementById('searchUser').addEventListener('input', function() {
        const search = this.value.toLowerCase();
        document.querySelectorAll('#userTableBody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
        });
    });
</script>

<style>
    .card { border-radius: 0.6rem; }
    .card .card-body { min-height: 250px; }
    thead.sticky-top { z-index: 2; }
    .table-responsive {
        scrollbar-width: thin;
        scrollbar-color: #B39DDB transparent;
    }
    .table-responsive::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background-color: #B39DDB;
        border-radius: 4px;
    }
</style>
@endsection
