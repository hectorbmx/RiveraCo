@if($rutaImagen = $imagenesTipoPresupuesto[$tipoImagen] ?? null)
    <img src="{{ asset($rutaImagen) }}" alt="" class="{{ $clasesImagen }} rounded-md bg-[#0B265A] p-0.5">
@endif
