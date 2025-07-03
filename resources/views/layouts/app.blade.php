@include('H_F.header')

<div class="container-fluid">
    <div class="row">
        @auth
            @if (Auth::user()->admin)
                <div class="col-md-2">
                    @include('layouts.sidebar-admin')
                </div>
                <main class="col-md-10">
                    @yield('content')
                </main>
            @else
                <main class="col-12">
                    @yield('content')
                </main>
            @endif
        @else
            <main class="col-12">
                @yield('content')
            </main>
        @endauth
    </div>
</div>

@include('H_F.footer')
