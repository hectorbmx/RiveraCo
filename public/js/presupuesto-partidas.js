window.editorPresupuestoPartidas = (estado, url, csrf, unidades) => ({
    partidas: [],
    notaAdicional: [],
    notas: [],
    notasEnEdicion: [],
    errorEditorNota: false,
    tasaIva: estado.tasa_iva,
    revision: estado.revision,
    siguienteClave: 0,
    baseGuardada: '',
    guardando: false,
    mensaje: '',
    error: '',
    importacion: null,
    importando: false,
    errorImportacion: '',

    init() {
        this.cargar(estado);
    },

    cargar(datos) {
        this.revision = datos.revision;
        this.tasaIva = datos.tasa_iva;
        this.notaAdicional = datos.nota_ejecucion;
        this.notas = datos.notas;
        this.partidas = datos.partidas.map(partida => ({
            ...partida,
            clave: ++this.siguienteClave,
            subpartidas: partida.subpartidas.map(fila => ({ ...fila, clave: ++this.siguienteClave })),
        }));
        this.baseGuardada = JSON.stringify(this.captura());
    },

    abrirNotas() {
        this.notasEnEdicion = [...this.notas];
        this.$refs.notasImportantes.showModal();
    },

    aplicarNotas() {
        this.notas = this.notasEnEdicion.map(nota => nota.trim()).filter(Boolean);
        this.$refs.notasImportantes.close();
    },

    abrirImportacion() {
        this.importacion = null;
        this.errorImportacion = '';
        this.$refs.archivoImportacion.value = '';
        this.$refs.dialogoImportacion.showModal();
    },

    async previsualizarExcel(urlImportacion) {
        if (this.importando) return;
        const archivo = this.$refs.archivoImportacion.files[0];
        if (!archivo) return;
        this.importando = true;
        this.errorImportacion = '';
        this.importacion = null;
        const contenido = new FormData();
        contenido.append('archivo', archivo);

        try {
            const respuesta = await fetch(urlImportacion, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: contenido,
            });
            const datos = await respuesta.json().catch(() => null);
            if (!respuesta.ok || !datos?.data) {
                this.errorImportacion = datos?.errors?.archivo?.[0] || datos?.message || 'No se pudo leer el Excel.';
                return;
            }
            this.importacion = datos.data;
        } catch {
            this.errorImportacion = 'No se pudo conectar para leer el Excel.';
        } finally {
            this.importando = false;
        }
    },

    unidadDisponible(unidad) {
        return unidades.includes(unidad);
    },

    aplicarImportacion(reemplazar) {
        if (!this.importacion || this.importando) return;
        const partidasNuevas = this.importacion.partidas.map(({ nombre, subpartidas }) => ({
            id: null, clave: ++this.siguienteClave, nombre,
            subpartidas: subpartidas.map(({ concepto, unidad, cantidad, precio_unitario }) => ({
                id: null, clave: ++this.siguienteClave, concepto, cantidad, precio_unitario,
                unidad: this.unidadDisponible(unidad) ? unidad : '',
            })),
        }));
        this.partidas = reemplazar ? partidasNuevas : [...this.partidas, ...partidasNuevas];
        this.error = '';
        this.mensaje = 'Partidas cargadas en la tabla. Guarda los cambios para conservarlas.';
        this.$refs.dialogoImportacion.close();
        this.enfocarPartida(partidasNuevas[0].clave);
    },

    agregarPartida() {
        const clave = ++this.siguienteClave;
        this.partidas.push({ id: null, clave, nombre: '', subpartidas: [] });
        this.enfocarPartida(clave);
    },

    enfocarPartida(clave) {
        this.$nextTick(() => {
            const nombre = document.getElementById('nombre-' + clave);
            nombre?.scrollIntoView({ block: 'center', inline: 'nearest' });
            nombre?.focus({ preventScroll: true });
        });
    },

    agregarSubpartida(partida) {
        const clave = ++this.siguienteClave;
        partida.subpartidas.push({ id: null, clave, concepto: '', unidad: '', cantidad: '', precio_unitario: '' });
        this.$nextTick(() => document.getElementById('concepto-' + clave)?.focus());
    },

    mover(filas, indice, paso) {
        const [fila] = filas.splice(indice, 1);
        filas.splice(indice + paso, 0, fila);
    },

    quitarPartida(indice) {
        if (this.partidas[indice].subpartidas.length &&
            !window.confirm('¿Eliminar esta partida y todas sus subpartidas al guardar?')) return;
        this.partidas.splice(indice, 1);
    },

    letra(indice) {
        let numero = indice + 1;
        let clave = '';
        while (numero > 0) {
            numero--;
            clave = String.fromCharCode(65 + numero % 26) + clave;
            numero = Math.floor(numero / 26);
        }
        return clave;
    },

    centesimas(valor) {
        const coincidencia = /^(\d*)(?:\.(\d{0,2}))?$/.exec(String(valor));
        if (!coincidencia || !(coincidencia[1] || coincidencia[2])) return null;
        return BigInt(coincidencia[1] || '0') * 100n + BigInt((coincidencia[2] || '').padEnd(2, '0'));
    },

    importe(fila) {
        const cantidad = this.centesimas(fila.cantidad);
        const precio = this.centesimas(fila.precio_unitario);
        return cantidad === null || precio === null ? null : cantidad * precio;
    },

    subtotal(partida) {
        return partida.subpartidas.reduce((suma, fila) => suma + (this.importe(fila) ?? 0n), 0n);
    },

    get totales() {
        const subtotal = this.partidas.reduce((suma, partida) => suma + this.subtotal(partida), 0n);
        const tasa = this.centesimas(this.tasaIva);
        const iva = tasa === null ? null : subtotal * tasa;
        return { subtotal, iva, total: iva === null ? null : subtotal * 10000n + iva };
    },

    moneda(valor, escala = 10000n) {
        if (valor === null) return '—';
        const divisor = escala / 100n;
        const centavos = (valor + divisor / 2n) / divisor;
        return '$' + (centavos / 100n).toLocaleString('es-MX') + '.' + String(centavos % 100n).padStart(2, '0');
    },

    captura() {
        const partidas = this.partidas.map(({ id, nombre, subpartidas }) => ({
            id, nombre, subpartidas: subpartidas.map(({ id, concepto, unidad, cantidad, precio_unitario }) => ({
                id, concepto, unidad, cantidad, precio_unitario,
            })),
        }));
        return {
            revision: this.revision,
            tasa_iva: this.tasaIva,
            partidas,
            nota_ejecucion: JSON.stringify(this.notaAdicional),
            notas: this.notas,
        };
    },

    get hayCambios() {
        return this.baseGuardada !== JSON.stringify(this.captura());
    },

    async guardarVersion(formulario) {
        if (this.guardando || !this.$refs.tabla.reportValidity()) return;
        if (this.hayCambios && !(await this.guardar())) return;
        formulario.elements.namedItem('revision_partidas').value = this.revision;
        this.guardando = true;
        window.showGlobalSubmitLoader?.('Guardando versión…');
        formulario.submit();
    },

    async guardar() {
        if (this.guardando) return false;
        this.guardando = true;
        this.mensaje = '';
        this.error = '';
        window.showGlobalSubmitLoader?.('Guardando partidas…');
        try {
            const respuesta = await fetch(url, {
                method: 'PATCH',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(this.captura()),
            });
            const datos = await respuesta.json().catch(() => null);
            if (!respuesta.ok || !datos?.ok) {
                const validacion = Object.values(datos?.errors ?? {})[0]?.[0];
                this.error = validacion || datos?.message || 'No se pudieron guardar las partidas.';
                return false;
            }
            this.cargar(datos.data);
            this.mensaje = 'Partidas y notas guardadas.';
            return true;
        } catch {
            this.error = 'No se pudo conectar con el servidor.';
            return false;
        } finally {
            this.guardando = false;
            window.hideGlobalSubmitLoader?.();
        }
    },
});
