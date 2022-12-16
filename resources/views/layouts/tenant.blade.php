<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    <script src="{{ mix('js/app.js') }}" defer></script>

    <!-- Styles -->
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">


    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    @stack('head')
</head>

<body class="bg-gray-500 h-screen antialiased flex">


    {{-- Side Nav (Desktop) --}}
    <nav class="hidden lg:block w-64 h-full">
        <div class="w-full h-full bg-gray-50 p-3">

            test

            
        </div>
    </nav>

    <div id="app">




    {{-- Top Nav (Mobile) --}}
    <nav class="bg-red-500 block lg:hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div>
                    <a href="{{ route('tenant.posts.index') }}" class="text-sm font-medium text-white">Posts
                    </a>
                </div>


                <div class="">
                    <div class="ml-4 flex items-center md:ml-6">
                        @guest
                            <x-button class="mt-1" as="a" href="{{ route('tenant.login') }}">Login</x-button>

                            @if (Route::has('tenant.register'))
                                <x-button class="mt-1 ml-2" as="a" href="{{ route('tenant.register') }}">Register</x-button>
                            @endif
                        @else
                            <div x-data="{ expanded: false }" @click.outside="expanded = false" class="ml-3 relative z-10">
                                <div>
                                    <button @click="expanded = !expanded" class="max-w-xs flex items-center text-sm rounded-full text-white focus:outline-none">
                                        <img class="h-8 w-8 rounded-full" src="{{ auth()->user()->gravatar_url }}" alt="{{ auth()->user()->name }}">
                                    </button>
                                </div>
                                <div x-show="expanded" class="absolute right-0 mt-2 w-48 rounded-md shadow-lg" style="display: none;">
                                    <div class="py-1 rounded-md bg-white ring-1 ring-black ring-opacity-5">
                                        <a href="{{ route('tenant.settings.user') }}" class="block px-4 py-1 text-sm text-gray-700 hover:bg-gray-100">My account
                                        </a>
                                        @if (auth()->user()->isOwner())
                                            <a href="{{ route('tenant.settings.application') }}" class="block px-4 py-1 text-sm text-gray-700 hover:bg-gray-100">Application settings
                                            </a>
                                            <a href="{{ config('nova.path') }}" class="block px-4 py-1 text-sm text-gray-700 hover:bg-gray-100">Admin panel
                                            </a>
                                        @endif
                                        <a href="{{ route('tenant.logout') }}" class="block px-4 py-1 text-sm text-gray-700 hover:bg-gray-100"
                                            onclick="event.preventDefault();
                                        document.getElementById('logout-form').submit();">
                                            {{ __('Logout') }}
                                        </a>
                                        <form id="logout-form" action="{{ route('tenant.logout') }}" method="POST" class="hidden">
                                            {{ csrf_field() }}
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endguest
                    </div>
                </div>
            </div>
        </div>
    </nav>


    @if (isset($title))
        <header class="bg-white border-b border-gray-200 w-full">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-semibold text-gray-900">
                    {{ $title }}
                </h1>
            </div>
        </header>
    @endif

    <main class="w-full">
        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            @if (session('success'))
                <div x-data="{ show: true }" x-show="show" class="rounded-md bg-green-50 p-4 mb-6">
                    <div class="flex">
                        <div class="shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm leading-5 font-medium text-green-800">
                                {{ session('success') }}
                            </p>
                        </div>
                        <div class="ml-auto pl-3">
                            <div class="-mx-1.5 -my-1.5">
                                <button @click="show = false" class="inline-flex rounded-md p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:bg-green-100 transition ease-in-out duration-150">
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @yield('content')
        </div>
    </main>
    </div>

    @stack('body')
</body>

</html>
