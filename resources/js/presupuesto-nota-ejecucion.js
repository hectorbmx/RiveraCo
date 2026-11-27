import Quill from 'quill';
import 'quill/dist/quill.snow.css';

export function iniciarEditorNotaEjecucion(elemento, operaciones, alEditar, {
    etiqueta = 'titulo-nota-ejecucion',
    placeholder = 'Escribe las notas de ejecución para el cliente…',
} = {}) {
    const editor = new Quill(elemento, {
        theme: 'snow',
        placeholder,
        formats: ['bold', 'italic', 'list', 'color'],
        modules: { toolbar: ['bold', 'italic', { color: [] }, { list: 'ordered' }, { list: 'bullet' }] },
    });

    editor.root.setAttribute('aria-labelledby', etiqueta);
    editor.setContents(operaciones, 'silent');
    editor.on('text-change', () => {
        alEditar(editor.getText().trim() === '' ? [] : editor.getContents().ops);
    });
}
