<label for="etapa-fotos" class="mt-3 block text-sm font-medium text-slate-700">Agregar fotografías</label>
<input id="etapa-fotos" name="fotos[]" type="file" multiple accept="image/jpeg,image/png,image/webp"
       x-ref="fotosEtapa" @change="agregarFotos($event)" aria-describedby="etapa-fotos-ayuda"
       class="mt-1 block w-full text-sm text-slate-600">
<p id="etapa-fotos-ayuda" class="mt-1 text-xs text-slate-500">JPG, PNG o WebP. Máximo 8 MB por foto. Las nuevas fotos se adjuntarán al guardar.</p>
<div class="mt-2 text-sm text-red-700" role="alert" x-show="erroresFotos.length" x-cloak>
    <template x-for="(mensaje, indice) in erroresFotos" :key="indice"><p x-text="mensaje"></p></template>
</div>
<div class="mt-3" x-show="fotos.length" x-cloak>
    <p class="mb-2 text-xs font-semibold text-slate-600">Nuevas fotos pendientes: <span x-text="fotos.length"></span></p>
    <div class="flex flex-wrap gap-3">
        <template x-for="foto in fotos" :key="foto.id">
            <div class="w-32 rounded-lg border border-slate-200 bg-white p-2">
                <img :src="foto.url" :alt="foto.archivo.name" class="h-24 w-full rounded object-cover">
                <p class="mt-1 truncate text-xs text-slate-500" x-text="foto.archivo.name"></p>
                <button type="button" @click="quitarFoto(foto.id)" :aria-label="'Quitar ' + foto.archivo.name"
                        class="mt-1 text-xs font-semibold text-red-700 hover:underline">Quitar</button>
            </div>
        </template>
    </div>
</div>
