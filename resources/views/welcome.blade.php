<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie Sécurisée</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background-color: #BAA8D3;
            color: #171D1C;
            font-family: 'Roboto', sans-serif;
        }
        header {
            background-color: #695F76;
            color: white;
        }
        .btn-outline-light {
            border-color: #ffffff;
            color: #ffffff;
        }
        .btn-light {
            background-color: #BAA0CF;
            color: white;
            border: none;
        }
        .btn-light:hover {
            background-color: #9280A3;
        }
        .hero {
            background-color: #9280A3;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .divider {
            height: 4px;
            background-color: #695F76;
            margin: 40px 0;
        }
        h2 {
            color: #695F76;
        }
        h5 {
            color: #9280A3;
        }
        .features .feature {
            padding: 20px;
            background-color: #BAA0CF;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            color: #171D1C;
        }
        footer {
            background-color: #171D1C;
            color: white;
        }
    </style>
</head>
<body>
    <header class="py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Missive</h1>
            <div>
            <a href="{{ route('login') }}" class="btn btn-outline-light me-2">Se connecter</a>
    <a href="{{ route('register') }}" class="btn btn-light">S'inscrire</a>


            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1 class="display-4 fw-bold">Une Messagerie Sécurisée et Moderne</h1>
            <p class="lead">Protégez vos échanges avec notre solution avancée et conviviale.</p>
            <a href="/register" class="btn btn-light btn-lg mt-3">Commencer maintenant</a>
        </div>
    </section>

    <section class="container my-5">
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <img src="" class="img-fluid mb-3" alt="Sécurité">
                <h5>Confidentialité Maximale</h5>
                <p>Vos e-mails sont chiffrés et protégés contre toute intrusion.</p>
            </div>
            <div class="col-md-4 text-center">
                <img src="" class="img-fluid mb-3" alt="Facilité d'utilisation">
                <h5>Facilité d'utilisation</h5>
                <p>Une interface simple et intuitive, adaptée à tous les utilisateurs.</p>
            </div>
            <div class="col-md-4 text-center">
                <img src="" class="img-fluid mb-3" alt="Compatibilité">
                <h5>Compatibilité Totale</h5>
                <p>Fonctionne parfaitement avec les autres services de messagerie.</p>
            </div>
        </div>
    </section>

    <div class="divider"></div>

    <section class="container my-5">
        <h2 class="text-center mb-4">Pourquoi Choisir Notre Solution ?</h2>
        <div class="row features">
            <div class="col-md-6">
                <div class="feature">
                    <h5>Sécurité de Niveau Professionnel</h5>
                    <p>Des mécanismes avancés tels que DKIM, SPF, et DMARC pour sécuriser vos e-mails.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature">
                    <h5>Anti-Spam et Anti-Phishing</h5>
                    <p>Des filtres intelligents pour bloquer les spams, les virus, et les tentatives de phishing.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature">
                    <h5>Notifications en Temps Réel</h5>
                    <p>Restez informé de vos nouveaux e-mails instantanément.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="feature">
                    <h5>Hébergement Privé</h5>
                    <p>Contrôlez entièrement vos données avec une solution auto-hébergée.</p>
                </div>
            </div>
        </div>
    </section>

    <footer class="text-center py-3">
        <p>&copy; 2025 Messagerie Sécurisée - Tous droits réservés.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>