export function comisionEtapaFormulario() {
    return {
        guardando: false,
        fotos: [],
        erroresFotos: [],
        siguienteFotoId: 1,

        agregarFotos(event) {
            this.erroresFotos = [];
            const permitidos = ['image/jpeg', 'image/png', 'image/webp'];

            for (const archivo of Array.from(event.target.files || [])) {
                if (!permitidos.includes(archivo.type) || archivo.size === 0 || archivo.size > 8 * 1024 * 1024) {
                    this.erroresFotos.push(`No se agregó ${archivo.name}: usa JPG, PNG o WebP de hasta 8 MB.`);
                    continue;
                }
                if (this.fotos.some(foto => foto.archivo.name === archivo.name
                    && foto.archivo.size === archivo.size && foto.archivo.lastModified === archivo.lastModified)) {
                    continue;
                }
                this.fotos.push({ id: this.siguienteFotoId++, archivo, url: URL.createObjectURL(archivo) });
            }
            this.sincronizarFotos();
        },

        quitarFoto(id) {
            const foto = this.fotos.find(item => item.id === id);
            if (!foto) return;
            URL.revokeObjectURL(foto.url);
            this.fotos = this.fotos.filter(item => item.id !== id);
            this.sincronizarFotos();
        },

        sincronizarFotos() {
            const archivos = new DataTransfer();
            this.fotos.forEach(foto => archivos.items.add(foto.archivo));
            this.$refs.fotosEtapa.files = archivos.files;
        },

        enviar(event) {
            if (this.guardando || event.defaultPrevented) {
                event.preventDefault();
                return;
            }
            this.guardando = true;
            window.showGlobalSubmitLoader?.(event.currentTarget.dataset.loadingMessage || 'Guardando etapa y fotografías…');
        },

        destroy() {
            this.fotos.forEach(foto => URL.revokeObjectURL(foto.url));
        },
    };
}
