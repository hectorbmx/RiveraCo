import { comisionEtapaFormulario } from './comision-etapa-form';

export function comisionAltaFormulario() {
    const evidencias = comisionEtapaFormulario();
    return {
        ...evidencias,
        buscarPersonal: '',
        inicio: '',
        fin: '',

        init() {
            this.$nextTick(() => {
                this.cargarPila(false);
                this.actualizarEstado();
            });
        },

        cargarPila(reemplazar = false) {
            const opcion = this.$refs.altaPila.selectedOptions[0];
            if (!opcion?.value) return;
            const valores = [
                [this.$refs.altaDiametro, opcion.dataset.diametro],
                [this.$refs.altaProfundidad, opcion.dataset.profundidad],
                [this.$refs.altaMetros, opcion.dataset.profundidad],
            ];
            valores.forEach(([input, valor]) => {
                if (reemplazar || input.value === '') input.value = valor || '';
            });
        },

        actualizarEstado() {
            this.inicio = this.$refs.altaInicio.value;
            this.fin = this.$refs.altaFin.value;
        },

        coincidePersonal(texto) {
            const normalizar = valor => valor.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase().trim();
            return normalizar(texto).includes(normalizar(this.buscarPersonal));
        },

        get estadoPerforacion() {
            if (!this.inicio) return 'Sin iniciar';
            return this.fin ? 'Perforación completada' : 'Perforación en proceso';
        },
    };
}
