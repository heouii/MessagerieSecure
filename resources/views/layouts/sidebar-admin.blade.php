<nav id="sidebarMenu" class="sidebar d-md-block bg-gradient">
    <div class="position-sticky pt-3">
        <h5 class="text-white px-3 mb-3 fw-bold">Admin Panel</h5>
        <ul class="nav flex-column">
            <li class="nav-item mb-1">
                <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home me-2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('admin.users') }}" class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                    <i class="fas fa-users me-2"></i> Utilisateurs
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('admin.logs') }}" class="nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}">
                    <i class="fas fa-file-code me-2"></i> Logs JSON
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('admin.server.logs') }}" class="nav-link {{ request()->routeIs('admin.server.logs') ? 'active' : '' }}">
                    <i class="fas fa-server me-2"></i> Logs Serveur
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('profil.show') }}" class="nav-link {{ request()->routeIs('profil.show') ? 'active' : '' }}">
                    <i class="fas fa-user me-2"></i> Profil
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('admin.connexions') }}" class="nav-link {{ request()->routeIs('admin.connexions') ? 'active' : '' }}">
                    <i class="fas fa-clock me-2"></i> Connexions
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('admin.blacklists.index') }}" class="nav-link {{ request()->routeIs('admin.blacklists.*') ? 'active' : '' }}">
                    <i class="fas fa-ban me-2"></i> Blacklist
                </a>
            </li>
        </ul>
    </div>
</nav>
