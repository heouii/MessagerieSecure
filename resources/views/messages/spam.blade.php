@extends('layouts.app')

@section('content')
<div class="container my-4">
    <h1 class="mb-4">
        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
        Messages Marqués comme Spam
    </h1>

    @if($messages->isEmpty())
        <div class="alert alert-info">
            Aucun message spam détecté.
        </div>
    @else
        <div class="list-group">
            @foreach($messages as $message)
                <div class="list-group-item list-group-item-action mb-2 border border-danger rounded">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1 text-danger">
                            De : {{ $message->user->name ?? 'Expéditeur inconnu' }}
                        </h5>
                        <small class="text-muted">
                            Reçu le {{ $message->created_at->format('d/m/Y H:i') }}
                        </small>
                    </div>
                    <p class="mb-1">
                        {{ \Illuminate\Support\Str::limit($message->contenu, 200) }}
                    </p>
                    <div class="mt-2">
                        <form action="{{ route('messages.unspam', $message) }}" method="POST" class="d-inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-check"></i> Marquer comme non spam
                            </button>
                        </form>
                        <form action="{{ route('messages.destroy', $message) }}" method="POST" class="d-inline ms-2">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i> Supprimer
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>;
@endsection
