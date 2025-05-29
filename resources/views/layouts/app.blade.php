@include('H_F.header')
<div class="container-fluid">
    <div class="row">
        @auth
            <div class="col-md-2">
                @if (Auth::user()->admin)
                    @include('layouts.sidebar-admin')
                @else
                    @include('layouts.sidebar-user')
                @endif
            </div>
            <main class="col-md-10">
                @yield('content')
            </main>
        @else
            <main class="col-12">
                @yield('content')
            </main>
        @endauth
    </div>
</div>

@include('H_F.footer')