// Format tanggal sesuai preferensi proyek (mm/dd/yyyy atau dd/mm/yyyy).
export function formatDate(iso, format = 'mm/dd/yyyy') {
    if (!iso) return '—';
    const [y, m, d] = iso.split('-');
    return format === 'dd/mm/yyyy' ? `${d}/${m}/${y}` : `${m}/${d}/${y}`;
}

export function parseDate(iso) {
    return new Date(`${iso}T00:00:00`);
}

// Selisih hari kalender antara dua tanggal ISO.
export function daysBetween(aIso, bIso) {
    return Math.round((parseDate(bIso) - parseDate(aIso)) / 86400000);
}

// Tambah hari kalender ke tanggal ISO.
export function addDays(iso, days) {
    const date = parseDate(iso);
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
}

export function svgElement(name, attrs = {}) {
    const el = document.createElementNS('http://www.w3.org/2000/svg', name);
    for (const [key, value] of Object.entries(attrs)) {
        el.setAttribute(key, value);
    }
    return el;
}

export function clearChildren(el) {
    while (el.firstChild) el.removeChild(el.firstChild);
}
