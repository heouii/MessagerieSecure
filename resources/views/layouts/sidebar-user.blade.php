<nav class="col-md-2 d-none d-md-block sidebar bg-light" style="min-height: 100vh; padding: 1rem;">
    <div class="position-sticky">

        <div class="accordion" id="mailMenu">

            <div class="accordion-item">
                <h2 class="accordion-header" id="headingInbox">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInbox" aria-expanded="true" aria-controls="collapseInbox">
                        <i class="fas fa-inbox me-2"></i> Messages
                    </button>
                </h2>
                <div id="collapseInbox" class="accordion-collapse collapse show" aria-labelledby="headingInbox" data-bs-parent="#mailMenu">
                    <div class="accordion-body px-0">
                        <ul class="list-unstyled ps-3">
                            <li><a href="{{ route('messages.inbox') }}" class="d-block py-1 text-decoration-none"><i class="fas fa-inbox me-2"></i> Boîte de Réception</a></li>
                            <li><a href="{{ route('messages.sent') }}" class="d-block py-1 text-decoration-none"><i class="fas fa-paper-plane me-2"></i> Envoyés</a></li>
                            <li><a href="{{ route('messages.drafts') }}" class="d-block py-1 text-decoration-none"><i class="fas fa-edit me-2"></i> Brouillons</a></li>
                            <li><a href="{{ route('messages.spam') }}" class="d-block py-1 text-decoration-none"><i class="fas fa-exclamation-triangle me-2"></i> Spam</a></li>
                            <li><a href="{{ route('messages.deleted') }}" class="d-block py-1 text-decoration-none"><i class="fas fa-trash me-2"></i> Supprimés</a></li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>

        <hr>

        <a href="{{ route('profil.show') }}" class="d-block py-2 text-decoration-none"><i class="fas fa-user me-2"></i> Profil</a>
        <a href="{{ route('parametres') }}" class="d-block py-2 text-decoration-none"><i class="fas fa-cogs me-2"></i> Paramètres</a>
    </div>
</nav>
