@php($firmaCreadorUrl = \App\Support\Impresion\FirmaUsuario::url($usuarioFirma ?? null))
@if($firmaCreadorUrl)
    <div style="margin-top:4px"><img src="{{ $firmaCreadorUrl }}" alt="Firma de {{ $usuarioFirma->name }}" style="display:block;max-width:110px;max-height:38px;object-fit:contain"></div>
@endif
