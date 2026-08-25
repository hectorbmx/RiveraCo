<h2 class="text-lg font-semibold mb-4">Lista semanal de asistencia</h2>

@if(session('success'))
    <div class="mb-4 p-3 bg-green-100 text-green-700 rounded-lg text-sm">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
        Hay errores en el formulario, revisa la informacion.
    </div>
@endif

@include('obras.partials.asistencias._filters')
@include('obras.partials.asistencias._workflow')

@if(($daysCount ?? 0) !== 7)
    <div class="text-sm text-gray-500 mb-4">
        La vista semanal solo se muestra cuando el rango es de 7 dias, de lunes a domingo.
    </div>
@else
    @include('obras.partials.asistencias._weekly-list')
@endif

@include('obras.partials.asistencias._registered')
@include('obras.partials.asistencias._photo-modal')
