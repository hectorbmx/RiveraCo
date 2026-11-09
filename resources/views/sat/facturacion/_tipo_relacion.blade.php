@php($selectedRelacionTipo = old('relacion_tipo', $prefill['relacion_tipo'] ?? '04'))
<select name="relacion_tipo" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    <option value="04" @selected($selectedRelacionTipo === '04')>04 - Sustitución de los CFDI previos</option>
    <option value="01" @selected($selectedRelacionTipo === '01')>01 - Nota de crédito de los documentos relacionados</option>
    <option value="02" @selected($selectedRelacionTipo === '02')>02 - Nota de débito de los documentos relacionados</option>
    <option value="03" @selected($selectedRelacionTipo === '03')>03 - Devolución de mercancía sobre facturas o traslados previos</option>
    <option value="05" @selected($selectedRelacionTipo === '05')>05 - Traslados de mercancias facturados previamente</option>
    <option value="06" @selected($selectedRelacionTipo === '06')>06 - Factura generada por los traslados previos</option>
    <option value="07" @selected($selectedRelacionTipo === '07')>07 - CFDI por aplicación de anticipo</option>
</select>
