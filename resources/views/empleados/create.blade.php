@extends('layouts.admin')

@section('title', 'Nuevo empleado')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-[#0B265A] mb-4">Nuevo empleado</h1>

    @if($errors->any())
        <div class="mb-4 p-3 bg-red-100 text-red-700 rounded-lg text-sm">
            Hay errores en el formulario, revisa la información.
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow p-6">
      <form action="{{ route('empleados.store') }}" method="POST" enctype="multipart/form-data">
  @include('empleados._form', ['empleado' => new \App\Models\Empleado(), 'areas' => $areas])
</form>
    </div>
</div>
@endsection
