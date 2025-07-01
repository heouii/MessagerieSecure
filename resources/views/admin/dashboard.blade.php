@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h1 class="mb-4">Tableau de bord Administrateur</h1>

    <!-- Statistiques -->
    <div class="row mb-4 g-3">
        @php
            $stats = [
                ['label' => 'Utilisateurs inscrits', 'value' => $totalUsers, 'icon' => 'fas fa-users', 'color' => '#BAA8D3', 'link' => route('admin.users')],
                ['label' => 'Admins', 'value' => $totalAdmins, 'icon' => 'fas fa-user-shield', 'color' => '#BAA8D3'],
                ['label' => 'Messages envoyés', 'value' => $totalMessages, 'icon' => 'fas fa-envelope', 'color' => '#BAA8D3'],
                ['label' => 'Utilisateurs bloqués', 'value' => $blockedUsersCount, 'icon' => 'fas fa-user-lock', 'color' => '#BAA8D3', 'link' => route('admin.users')],
            ];
        @endphp

        @foreach ($stats as $stat)
        <div class="col-md-3">
            @if (isset($stat['link']))
                <a href="{{ $stat['link'] }}" class="text-decoration-none">
            @endif
            <div class="card shadow-sm" style="background-color: {{ $stat['color'] }}; color: white; cursor: {{ isset($stat['link']) ? 'pointer' : 'default' }};">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="fs-2">
                        <i class="{{ $stat['icon'] }}"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-1">{{ $stat['label'] }}</h5>
                        <p class="card-text fs-3 fw-bold m-0">{{ $stat['value'] }}</p>
                    </div>
                </div>
            </div>
            @if (isset($stat['link']))
                </a>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Graphiques -->
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">Inscriptions récentes</div>
                <div class="card-body">
                    <canvas id="userChart" style="max-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Derniers inscrits -->
    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold d-flex justify-content-between align-items-center">
                    <span>Derniers inscrits</span>
                    <input type="text" id="searchUser" class="form-control form-control-sm w-auto" placeholder="Rechercher...">
                </div>
                <div class="card-body table-responsive p-0" style="max-height: 400px;">
                    <table class="table table-hover table-striped mb-0">
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
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('userChart').getContext('2d');
    const userChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartLabels) !!},
            datasets: [{
                label: 'Inscriptions par jour',
                data: {!! json_encode($chartData) !!},
                borderColor: '#7D67A6',
                backgroundColor: 'rgba(186, 168, 211, 0.5)',
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false } },
                y: { beginAtZero: true }
            }
        }
    });

    // Recherche dans la table derniers inscrits
    document.getElementById('searchUser').addEventListener('input', function() {
        const search = this.value.toLowerCase();
        const rows = document.querySelectorAll('#userTableBody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(search) ? '' : 'none';
        });
    });
</script>

<style>
    /* Scroll barre table */
    .table-responsive {
        scrollbar-width: thin;
        scrollbar-color: #BAA8D3 transparent;
    }
    .table-responsive::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background-color: #BAA8D3;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: transparent;
    }

    /* Cards shadow douce */
    .card {
        border-radius: 10px;
    }

    /* Sticky table header */
    thead.table-light {
        position: sticky;
        top: 0;
        z-index: 10;
    }
</style>
@endsection
