<span style="display: flex; align-items: center;">
    <a href="{{ $link }}" target="_blank" style="margin-right: 8px;">
        {{ \Illuminate\Support\Str::limit($link, 30) }}
    </a>
    <button
        type="button"
        style="margin-left: 8px;"
        onclick="navigator.clipboard.writeText('{{ $link }}'); window.dispatchEvent(new CustomEvent('copied-link'));">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16h8M8 12h8m-7 8h8a2 2 0 002-2V8a2 2 0 00-2-2h-6a2 2 0 00-2 2v12zm4-16v4" /></svg>
    </button>
</span>
<script>
    window.addEventListener('copied-link', function() {
        window.Filament?.notifications?.push({
            title: 'Link berhasil disalin!',
            type: 'success'
        });
    });
</script>