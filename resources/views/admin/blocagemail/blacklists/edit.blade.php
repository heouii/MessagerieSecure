@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1>Modifier une entrée Blacklist</h1>

    <form method="POST" action="{{ route('admin.blacklists.update', $blacklist) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
            <label for="type" class="form-label">Type</label>
            <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                <option value="email" {{ $blacklist->type == 'email' ? 'selected' : '' }}>Email</option>
                <option value="domain" {{ $blacklist->type == 'domain' ? 'selected' : '' }}>Domaine</option>
            </select>
            @error('type')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="value" class="form-label">Adresse Email ou Domaine</label>
            <input type="text" name="value" id="value" class="form-control @error('value') is-invalid @enderror" value="{{ old('value', $blacklist->value) }}" required>
            @error('value')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <button class="btn btn-primary" type="submit">Enregistrer</button>
        <a href="{{ route('admin.blacklists.index') }}" class="btn btn-secondary">Retour</a>
    </form>
</div>
@endsection
