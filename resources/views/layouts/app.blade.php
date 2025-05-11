@include('H_F.header')

<div class="container-fluid">
    <div class="row">
        @auth
        <nav class="col-md-2 d-none d-md-block sidebar">
            <div class="position-sticky">
                <div class="accordion" id="mailMenu">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                            <a href="{{ route('dashboard') }}"><i class="fas fa-inbox"></i> Boîte de Réception</a>
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#mailMenu">
                            <div class="accordion-body">
                                <ul class="list-unstyled">
                                    <li><a href="{{ route('messages.inbox') }}"><i class="fas fa-inbox"></i> Boîte de Réception</a></li>
                                    <li><a href="{{ route('messages.sent') }}"><i class="fas fa-paper-plane"></i> Envoyés</a></li>
                                    <li><a href="{{ route('messages.drafts') }}"><i class="fas fa-edit"></i> Brouillons</a></li>
                                    <li><a href="{{ route('messages.spam') }}"><i class="fas fa-exclamation-triangle"></i> Spam</a></li>
                                    <li><a href="{{ route('messages.deleted') }}"><i class="fas fa-trash"></i> Supprimés</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <a href="{{ route('profil.show') }}"><i class="fas fa-user"></i> Profil</a>
                <a href="{{ route('parametres') }}" class="nav-link">Paramètres</a>
            </div>
        </nav>
        @endauth

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 content">
            @yield('content')
        </main>
    </div>
</div>

@include('H_F.footer')
