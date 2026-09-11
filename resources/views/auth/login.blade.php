{{-- resources/views/auth/login.blade.php --}}
<x-guest-layout>
    <style>
        /* ===== Silueta CSS de perforadora (estilo Bauer) ===== */
        :root {
            --rig-right: -4vw;       /* 👉 más negativo = se recorre más a la derecha */
            --rig-width: 30vw;
            --rig-opacity: 0.16;
            --rig-color: #FFFFFF;

            /* ===== Fila de pilas de concreto (fondo) ===== */
            --piles-color: #FFFFFF;
            --piles-opacity: 0.10;
            --piles-max-height: 16vh;  /* altura de la pila más alta */
        }

        .rig-stage {
            position: absolute;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .rig-silhouette {
            position: absolute;
            top: 0;
            bottom: 0;
            right: var(--rig-right);
            width: var(--rig-width);
            opacity: var(--rig-opacity);
        }

        .rig__track {
            position: absolute;
            bottom: 0;
            left: 3%;
            width: 82%;
            height: 2.6%;
            background: var(--rig-color);
            clip-path: polygon(5% 0%, 95% 0%, 100% 100%, 0% 100%);
        }

        .rig__chassis {
            position: absolute;
            bottom: 2.6%;
            left: 10%;
            width: 52%;
            height: 4.2%;
            background: var(--rig-color);
            clip-path: polygon(0% 100%, 0% 22%, 10% 0%, 100% 0%, 100% 100%);
        }

        .rig__cab {
            position: absolute;
            bottom: 6.6%;
            left: 12%;
            width: 15%;
            height: 3.2%;
            background: var(--rig-color);
            clip-path: polygon(0% 100%, 0% 12%, 60% 0%, 100% 0%, 100% 100%);
        }

        .rig__counterweight {
            position: absolute;
            bottom: 2.6%;
            left: 58%;
            width: 11%;
            height: 4.6%;
            background: var(--rig-color);
        }

        .rig__strut {
            position: absolute;
            bottom: 6.5%;
            left: 42%;
            width: 1.1%;
            height: 46%;
            background: var(--rig-color);
            transform: rotate(-22deg);
            transform-origin: bottom center;
        }

        .rig__mast {
            position: absolute;
            bottom: 9%;
            left: 36%;
            width: 6%;
            height: 86%;
            background:
                repeating-linear-gradient(
                    180deg,
                    var(--rig-color) 0px, var(--rig-color) 2px,
                    transparent 2px, transparent 20px
                ),
                linear-gradient(90deg,
                    var(--rig-color) 0, var(--rig-color) 2px,
                    transparent 2px, transparent calc(100% - 2px),
                    var(--rig-color) calc(100% - 2px), var(--rig-color) 100%
                );
        }

        .rig__mast::before,
        .rig__mast::after {
            content: "";
            position: absolute;
            left: 0;
            width: 100%;
            height: 100%;
            background: repeating-linear-gradient(
                115deg,
                transparent 0px, transparent 18px,
                var(--rig-color) 18px, var(--rig-color) 20px
            );
            opacity: 0.85;
        }
        .rig__mast::after {
            background: repeating-linear-gradient(
                65deg,
                transparent 0px, transparent 18px,
                var(--rig-color) 18px, var(--rig-color) 20px
            );
        }

        .rig__mast-b {
            position: absolute;
            bottom: 9%;
            left: 30%;
            width: 2.2%;
            height: 84%;
            background: var(--rig-color);
            opacity: 0.7;
        }

        .rig__head {
            position: absolute;
            bottom: 52%;
            left: 32%;
            width: 12%;
            height: 2.4%;
            background: var(--rig-color);
            border-radius: 2px;
        }

        .rig__kelly {
            position: absolute;
            bottom: 9%;
            left: 37.5%;
            width: 1.3%;
            height: 43%;
            background: var(--rig-color);
        }

        .rig__auger {
            position: absolute;
            bottom: 5%;
            left: 36.5%;
            width: 3%;
            height: 4.5%;
            background: repeating-linear-gradient(
                180deg,
                var(--rig-color) 0px, var(--rig-color) 2px,
                transparent 2px, transparent 5px
            );
        }

        /* ===== Fila de pilas de concreto en el fondo ===== */
        .piles-stage {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: var(--piles-max-height);
            pointer-events: none;
            z-index: 0;
            display: flex;
            align-items: flex-end;
            opacity: var(--piles-opacity);
        }

        .pile {
            background: var(--piles-color);
            border-radius: 4px 4px 0 0;
            margin: 0 0.35vw;
        }
        /* tapa de la pila (cabezal colado) */
        .pile::before {
            content: "";
            display: block;
            width: 130%;
            height: 6px;
            margin-left: -15%;
            background: var(--piles-color);
            border-radius: 2px;
        }

        @media (max-width: 1023px) {
            .rig-stage,
            .piles-stage { display: none; }
        }
    </style>

    <div class="relative min-h-screen w-full">

        {{-- Fila de pilas de concreto — va primero, más al fondo --}}
        <div class="piles-stage" aria-hidden="true">
            @php
                // alturas relativas (%) de cada pila, de izquierda a derecha
                $pileHeights = [35, 55, 25, 70, 45, 60, 30, 80, 50, 65, 38, 72, 42, 58, 28, 66, 48, 34];
            @endphp
            @foreach ($pileHeights as $h)
                <div class="pile" style="width: 2.4vw; height: {{ $h }}%;"></div>
            @endforeach
        </div>

        {{-- Silueta de perforadora --}}
        <div class="rig-stage" aria-hidden="true">
            <div class="rig-silhouette">
                <div class="rig__track"></div>
                <div class="rig__chassis"></div>
                <div class="rig__cab"></div>
                <div class="rig__counterweight"></div>
                <div class="rig__strut"></div>
                <div class="rig__mast-b"></div>
                <div class="rig__mast"></div>
                <div class="rig__head"></div>
                <div class="rig__kelly"></div>
                <div class="rig__auger"></div>
            </div>
        </div>

        <div class="relative z-10 w-full max-w-6xl mx-auto px-4 min-h-screen flex items-center">
            <div class="grid grid-cols-1 lg:grid-cols-[1.4fr_1fr] gap-8 items-center w-full">

                {{-- COLUMNA IZQUIERDA: HERO --}}
                <div class="hidden lg:flex flex-col justify-center space-y-8">
                    <div class="inline-flex items-center bg-[#1C355D] rounded-3xl px-10 py-6 shadow-xl shadow-black/20 border border-white/10">
                        <div class="w-16 h-16 rounded-2xl bg-[#FFC107] flex items-center justify-center font-bold text-[#0B265A] text-2xl">
                            RC
                        </div>
                        <div class="ml-6">
                            <p class="text-sm uppercase tracking-[0.2em] text-slate-300">Rivera Construcciones</p>
                            <p class="text-xl font-semibold text-white">Portal de Construcción</p>
                        </div>
                    </div>

                    <div>
                        <h1 class="text-4xl font-bold text-white mb-4 leading-tight">
                            Bienvenido al Portal de<br> Construcción
                        </h1>
                        <p class="text-slate-200 text-base max-w-xl">
                            Gestiona tus proyectos, equipos y recursos desde una sola plataforma
                            moderna e intuitiva, diseñada para el flujo de trabajo de Rivera Construcciones.
                        </p>
                    </div>

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
                        <div class="mb-8">
                            <h2 class="text-2xl font-semibold text-slate-900">Iniciar Sesión</h2>
                            <p class="text-sm text-slate-500 mt-1">
                                Ingresa tus credenciales para continuar.
                            </p>
                        </div>

                        <form method="POST" action="{{ route('login') }}" class="space-y-6">
                            @csrf

                            <div>
                                <x-input-label for="email" value="Correo electrónico" class="text-sm text-slate-700" />
                                <div class="mt-1 ">
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

                            <div x-data="{ show: false }">
                                <div class="flex items-center justify-between">
                                    <x-input-label for="password" value="Contraseña" class="text-sm text-slate-700" />

                                    @if (Route::has('password.request'))
                                        <a class="text-xs font-medium text-[#FFC107] hover:text-[#e0ac05]"
                                           href="{{ route('password.request') }}">
                                            ¿Olvidaste tu contraseña?
                                        </a>
                                    @endif
                                </div>

                                <div class="mt-1 relative">
                                    <x-text-input
                                        id="password"
                                        name="password"
                                        ::type="show ? 'text' : 'password'"
                                        required
                                        autocomplete="current-password"
                                        class="block w-full rounded-xl border-slate-200 pr-12 focus:border-[#FFC107] focus:ring-[#FFC107]"
                                    />

                                    <button
                                        type="button"
                                        @click="show = !show"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    >
                                        <svg x-show="!show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>

                                        <svg x-show="show" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M3 3l18 18" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M10.477 10.476a3 3 0 004.242 4.243" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9.88 5.09A9.953 9.953 0 0112 5c4.478 0 8.27 2.943 9.543 7a9.97 9.97 0 01-1.563 3.029M6.228 6.228A9.965 9.965 0 002.458 12c1.274 4.057 5.065 7 9.542 7a9.95 9.95 0 005.197-1.46" />
                                        </svg>
                                    </button>
                                </div>

                                <x-input-error :messages="$errors->get('password')" class="mt-1" />
                            </div>

                            <div class="flex items-center justify-between">
                                <label for="remember_me" class="inline-flex items-center">
                                    <input id="remember_me" type="checkbox"
                                           class="rounded border-slate-300 text-[#FFC107] shadow-sm focus:ring-[#FFC107]"
                                           name="remember">
                                    <span class="ms-2 text-xs text-slate-600">Recuérdame</span>
                                </label>
                            </div>

                            <div class="pt-2">
                                <x-primary-button
                                    class="w-full justify-center rounded-xl bg-[#FFC107] text-[#0B265A] font-semibold hover:bg-[#e0ac05] focus:ring-[#FFC107]">
                                    Iniciar Sesión
                                </x-primary-button>
                            </div>

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
    </div>
</x-guest-layout>