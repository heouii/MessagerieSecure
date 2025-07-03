@extends('layouts.app')

@section('content')
<div class="blacklist-edit-page">
  <div class="form-wrapper">
    <h1>Modifier une entrée Blacklist</h1>

    <form method="POST" action="{{ route('admin.blacklists.update', $blacklist) }}" novalidate>
      @csrf
      @method('PUT')

      <div class="form-group">
        <label for="type">Type</label>
        <select name="type" id="type" class="@error('type') invalid @enderror" required>
          <option value="email" {{ $blacklist->type == 'email' ? 'selected' : '' }}>Email</option>
          <option value="domain" {{ $blacklist->type == 'domain' ? 'selected' : '' }}>Domaine</option>
        </select>
        @error('type')
          <p class="error-msg">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-group">
        <label for="value">Adresse Email ou Domaine</label>
        <input type="text" name="value" id="value" value="{{ old('value', $blacklist->value) }}" class="@error('value') invalid @enderror" placeholder="exemple@domaine.com" required>
        @error('value')
          <p class="error-msg">{{ $message }}</p>
        @enderror
      </div>

      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="{{ route('admin.blacklists.index') }}" class="btn btn-secondary">Retour</a>
      </div>
    </form>
  </div>
</div>

<style>
  .blacklist-edit-page {
    min-height: calc(100vh - 80px);
    background: #faf8ff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
  }

  .form-wrapper {
    background: #fff;
    max-width: 600px;
    width: 100%;
    padding: 36px 40px;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(111, 66, 193, 0.25);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }

  .form-wrapper h1 {
    color: #5a2fae;
    font-weight: 700;
    font-size: 2.2rem;
    margin-bottom: 40px;
    text-align: center;
  }

  .form-group {
    margin-bottom: 28px;
    display: flex;
    flex-direction: column;
  }

  label {
    font-weight: 600;
    font-size: 1rem;
    color: #472476;
    margin-bottom: 8px;
    user-select: none;
  }

  input[type="text"],
  select {
    font-size: 1rem;
    padding: 12px 16px;
    border-radius: 8px;
    border: 2px solid #9a7eea;
    color: #3e2d6b;
    transition: border-color 0.3s ease;
  }

  input[type="text"]::placeholder {
    color: #bba7ed;
    font-style: italic;
  }

  input[type="text"]:focus,
  select:focus {
    border-color: #6f42c1;
    outline: none;
  }

  .invalid {
    border-color: #e53e3e !important;
  }

  .error-msg {
    color: #d03a3a;
    margin-top: 6px;
    font-weight: 600;
    font-size: 0.9rem;
  }

  .form-actions {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 32px;
  }

  .btn {
    font-weight: 700;
    font-size: 1.1rem;
    padding: 12px 36px;
    border-radius: 12px;
    cursor: pointer;
    border: none;
    user-select: none;
    text-decoration: none;
    transition: background-color 0.3s ease;
  }

  .btn-primary {
    background-color: #6f42c1;
    color: #fff;
  }

  .btn-primary:hover {
    background-color: #582cae;
  }

  .btn-secondary {
    background: transparent;
    color: #6f42c1;
    border: 2px solid #6f42c1;
  }

  .btn-secondary:hover {
    background-color: #6f42c1;
    color: white;
  }

  @media (max-width: 600px) {
    .form-wrapper {
      padding: 28px 20px;
    }

    .form-actions {
      flex-direction: column;
      gap: 12px;
    }

    .btn {
      width: 100%;
      text-align: center;
    }
  }
</style>
@endsection
