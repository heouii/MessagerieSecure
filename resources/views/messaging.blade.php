@extends('layouts.app')

@section('content')
<div class="flex h-screen">
    <!-- Sidebar -->
    @include('partials.sidebar')

    <!-- Zone principale -->
    <div class="flex-1 flex flex-col">
        <!-- Header avec recherche -->
        <div class="bg-white border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <h2 id="viewTitle" class="text-xl font-semibold text-gray-800">Boîte de réception</h2>
                    <button id="refreshBtn" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
                
                <div class="flex items-center space-x-2">
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Rechercher..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-2" 
                               style="--tw-ring-color: #BAA8D3; border-color: #BAA8D3;" 
                               onfocus="this.style.borderColor='#9280A3'" 
                               onblur="this.style.borderColor='#BAA8D3'">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des emails -->
        <div class="flex-1 overflow-y-auto">
            <div id="emailList" class="divide-y divide-gray-200">
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-4"></i>
                    <p>Chargement des messages...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales -->
@include('partials.compose-modal')
@include('partials.read-modal')
<div id="notifications" class="fixed top-4 right-4 space-y-2 z-50"></div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/messaging.css') }}">
@endpush



@push('scripts')
<script src="{{ asset('js/utils.js') }}"></script>
<script src="{{ asset('js/attachments.js') }}"></script>
<script src="{{ asset('js/autocomplete.js') }}"></script>
<script src="{{ asset('js/email-handlers.js') }}"></script>
<script src="{{ asset('js/reply-handlers.js') }}"></script>
<script src="{{ asset('js/messaging.js') }}"></script>
@endpush
