{{-- resources/views/auth/login.blade.php --}}
<x-guest-layout>
    <div class="w-full max-w-6xl mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-[1.4fr_1fr] gap-8 items-center">

            {{-- COLUMNA IZQUIERDA: HERO --}}
            <div class="hidden lg:flex flex-col justify-center space-y-8">
                {{-- Logo --}}
                <div class="inline-flex items-center bg-[#1C355D] rounded-3xl px-10 py-6 shadow-xl shadow-black/20 border border-white/10">
                    {{-- Aquí puedes reemplazar por <img> del logo real --}}
                    <div class="w-16 h-16 rounded-2xl bg-[#FFC107] flex items-center justify-center font-bold text-[#0B265A] text-2xl">
                        RC
                    </div>
                    <div class="ml-6">
                        <p class="text-sm uppercase tracking-[0.2em] text-slate-300">Rivera Construcciones</p>
                        <p class="text-xl font-semibold text-white">Portal de Construcción</p>
                    </div>
                </div>

                {{-- Texto principal --}}
                <div>
                    <h1 class="text-4xl font-bold text-white mb-4 leading-tight">
                        Bienvenido al Portal de<br> Construcción
                    </h1>
                    <p class="text-slate-200 text-base max-w-xl">
                        Gestiona tus proyectos, equipos y recursos desde una sola plataforma
                        moderna e intuitiva, diseñada para el flujo de trabajo de Rivera Construcciones.
                    </p>
                </div>

                {{-- Botones / secciones (solo visual, luego los convertimos en links reales) --}}
                <div class="flex flex-wrap gap-4 pt-4">
                    <div class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-[#1C355D] border border-white/10 shadow-lg shadow-black/20">
                        <div class="w-9 h-9 rounded-xl bg-[#FFC107] flex items-center justify-center text-[#0B265A] text-sm font-semibold">
                            OB
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Obras</p>
                            <p class="text-xs text-slate-300">Control de proyectos</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-[#1C355D] border border-white/10 shadow-lg shadow-black/20">
                        <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-[#FFC107] text-sm font-semibold">
                            RP
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Reportes</p>
                            <p class="text-xs text-slate-300">Indicadores clave</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 px-5 py-3 rounded-2xl bg-[#1C355D] border border-white/10 shadow-lg shadow-black/20">
                        <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-[#FFC107] text-sm font-semibold">
                            EQ
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-white">Equipos</p>
                            <p class="text-xs text-slate-300">Personal y recursos</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- COLUMNA DERECHA: CARD DE LOGIN --}}
            <div class="flex justify-center">
                <div class="w-full max-w-md bg-white rounded-3xl shadow-2xl shadow-black/30 px-8 py-10">
                    {{-- Título --}}
                    <div class="mb-8">
                        <h2 class="text-2xl font-semibold text-slate-900">Iniciar Sesión</h2>
                        <p class="text-sm text-slate-500 mt-1">
                            Ingresa tus credenciales para continuar.
                        </p>
                    </div>

                    {{-- Formulario de Breeze --}}
                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf

                        {{-- Email --}}
                        <div>
                            <x-input-label for="email" value="Correo electrónico" class="text-sm text-slate-700" />
                            <div class="mt-1">
                                <x-text-input id="email"
                                              class="block w-full rounded-xl border-slate-200 focus:border-[#FFC107] focus:ring-[#FFC107]"
                                              type="email"
                                              name="email"
                                              :value="old('email')"
                                              required
                                              autofocus
                                              autocomplete="username" />
                            </div>
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        {{-- Password --}}
                        <div>
                            <div class="flex items-center justify-between">
                                <x-input-label for="password" value="Contraseña" class="text-sm text-slate-700" />
                                @if (Route::has('password.request'))
                                    <a class="text-xs font-medium text-[#FFC107] hover:text-[#e0ac05]"
                                       href="{{ route('password.request') }}">
                                        ¿Olvidaste tu contraseña?
                                    </a>
                                @endif
                            </div>

                            <div class="mt-1">
                                <x-text-input id="password"
                                              class="block w-full rounded-xl border-slate-200 focus:border-[#FFC107] focus:ring-[#FFC107]"
                                              type="password"
                                              name="password"
                                              required
                                              autocomplete="current-password" />
                            </div>
                            <x-input-error :messages="$errors->get('password')" class="mt-1" />
                        </div>

                        {{-- Remember me --}}
                        <div class="flex items-center justify-between">
                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me" type="checkbox"
                                       class="rounded border-slate-300 text-[#FFC107] shadow-sm focus:ring-[#FFC107]"
                                       name="remember">
                                <span class="ms-2 text-xs text-slate-600">Recuérdame</span>
                            </label>
                        </div>

                        {{-- Botón --}}
                        <div class="pt-2">
                            <x-primary-button
                                class="w-full justify-center rounded-xl bg-[#FFC107] text-[#0B265A] font-semibold hover:bg-[#e0ac05] focus:ring-[#FFC107]">
                                Iniciar Sesión
                            </x-primary-button>
                        </div>

                        {{-- Registro (si lo usas) --}}
                        @if (Route::has('register'))
                            <p class="mt-4 text-center text-xs text-slate-500">
                                ¿Eres nuevo?
                                <a href="{{ route('register') }}" class="font-semibold text-[#FFC107] hover:text-[#e0ac05]">
                                    Registra tu cuenta
                                </a>
                            </p>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
