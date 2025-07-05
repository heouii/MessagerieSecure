<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Messagerie Sécurisée')</title>
    
    <!-- CSS External Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('css/messaging.css') }}">
    
    @stack('styles')
</head>
<body>
    <!-- Navigation -->
    @include('partials.navbar')
    
    <!-- Main Content -->
    @yield('content')
    
    <!-- Footer -->
    @include('partials.footer')
    
    <!-- JavaScript -->
    @stack('scripts')
</body>
</html>