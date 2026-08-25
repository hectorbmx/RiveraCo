<div id="asistenciaPhotoModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60" onclick="closeAsistenciaPhoto()"></div>

    <div class="relative max-w-3xl mx-auto mt-10 bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b">
            <div class="font-semibold text-slate-800" id="asistenciaPhotoTitle">Foto</div>
            <button type="button" class="text-slate-500 hover:text-slate-800" onclick="closeAsistenciaPhoto()">x</button>
        </div>

        <div class="p-4 bg-slate-50">
            <img id="asistenciaPhotoImg" src="" alt="Foto asistencia" class="w-full max-h-[70vh] object-contain rounded-lg bg-white">
        </div>
    </div>
</div>

<script>
    function openAsistenciaPhoto(btn) {
        const url = btn.getAttribute('data-photo-url');
        const title = btn.getAttribute('data-photo-title') || 'Foto';
        const modal = document.getElementById('asistenciaPhotoModal');
        const img = document.getElementById('asistenciaPhotoImg');
        const ttl = document.getElementById('asistenciaPhotoTitle');

        ttl.textContent = title;
        img.src = url;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeAsistenciaPhoto() {
        const modal = document.getElementById('asistenciaPhotoModal');
        const img = document.getElementById('asistenciaPhotoImg');

        img.src = '';
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeAsistenciaPhoto();
    });
</script>
