<nav id="sidebarMenu" class="col-md-2 d-md-block sidebar collapse">
    <div class="position-sticky pt-3">
        <h5 class="text-white px-3 mb-3">Admin Panel</h5>
        <ul class="nav flex-column">
            <li class="nav-item mb-1">
                <a href="{{ route('admin.dashboard') }}" 
                   class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-home me-2"></i> Dashboard Admin
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('admin.users') }}" 
                   class="nav-link {{ request()->routeIs('admin.users') ? 'active' : '' }}">
                    <i class="fas fa-users me-2"></i> Utilisateurs
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('admin.logs') }}" class="nav-link {{ request()->routeIs('admin.logs') ? 'active' : '' }}"><i class="fas fa-file-alt me-2"></i> Logs
                </a>
            </li>
            <li class="nav-item mb-1">
                <a href="{{ route('profil.show') }}" 
                   class="nav-link {{ request()->routeIs('profil.show') ? 'active' : '' }}">
                    <i class="fas fa-user me-2"></i> Profil
                </a>
            </li>
        </ul>
    </div>
</nav>
