// Resource chart: beban kerja tiap anggota tim pada timeline (SVG).
import { formatDate, daysBetween, svgElement, clearChildren } from './common.js';

const ROW_HEIGHT = 34;
const HEADER_HEIGHT = 36;
const NAME_WIDTH = 200;
const DAY_WIDTH = 20;
const MIN_TIMELINE = 560;

export function initResourceChart(container) {
    const url = container.dataset.url;

    fetch(url)
        .then((res) => res.json())
        .then(render)
        .catch((err) => {
            container.innerHTML = `<p class="text-red-600 text-sm">Gagal memuat resource chart: ${err}</p>`;
        });

    function render(data) {
        if (!data.resources.length) {
            container.innerHTML = '<p class="text-slate-500 text-sm p-4">Belum ada anggota tim. Tambahkan di tab Tim &amp; Alokasi.</p>';
            return;
        }

        if (!data.project.start || !data.project.end) {
            container.innerHTML = '<p class="text-slate-500 text-sm p-4">Belum ada tugas untuk menampilkan timeline.</p>';
            return;
        }

        const fmt = data.project.date_format;
        const totalDays = Math.max(1, daysBetween(data.project.start, data.project.end) + 1);
        const timelineWidth = Math.max(MIN_TIMELINE, totalDays * DAY_WIDTH);
        const width = NAME_WIDTH + timelineWidth + 20;
        const height = HEADER_HEIGHT + data.resources.length * ROW_HEIGHT + 12;

        clearChildren(container);

        const svg = svgElement('svg', { width, height, viewBox: `0 0 ${width} ${height}`, class: 'block bg-white' });

        // Header tanggal.
        for (let d = 0; d < totalDays; d++) {
            const iso = addDaysIso(data.project.start, d);
            const x = NAME_WIDTH + d * DAY_WIDTH;
            const date = new Date(`${iso}T00:00:00`);

            svg.appendChild(svgElement('rect', {
                x, y: 0, width: DAY_WIDTH, height: HEADER_HEIGHT,
                fill: '#f8fafc', stroke: '#cbd5e1', 'stroke-width': 0.5,
            }));

            const label = svgElement('text', {
                x: x + DAY_WIDTH / 2, y: 14, 'text-anchor': 'middle', 'font-size': 9, fill: '#64748b',
            });
            label.textContent = date.getDate();
            svg.appendChild(label);

            if (date.getDate() === 1 || d === 0) {
                const month = svgElement('text', {
                    x: x + 2, y: 30, 'font-size': 10, 'font-weight': 600, fill: '#334155',
                });
                month.textContent = date.toLocaleString('en', { month: 'short' }) + ' ' + String(date.getFullYear()).slice(2);
                svg.appendChild(month);
            }
        }

        data.resources.forEach((resource, i) => {
            const y = HEADER_HEIGHT + i * ROW_HEIGHT;

            svg.appendChild(svgElement('rect', {
                x: 0, y, width: NAME_WIDTH, height: ROW_HEIGHT,
                fill: i % 2 ? '#f8fafc' : '#ffffff', stroke: '#e2e8f0', 'stroke-width': 0.5,
            }));
            svg.appendChild(svgElement('rect', {
                x: NAME_WIDTH, y, width: timelineWidth, height: ROW_HEIGHT,
                fill: i % 2 ? '#fcfcfd' : '#ffffff', stroke: '#f1f5f9', 'stroke-width': 0.5,
            }));

            const nameText = svgElement('text', {
                x: 10, y: y + ROW_HEIGHT / 2 - 2, 'font-size': 12, 'font-weight': 600, fill: '#1e293b',
            });
            nameText.textContent = truncate(resource.name, 22);
            svg.appendChild(nameText);

            const roleText = svgElement('text', {
                x: 10, y: y + ROW_HEIGHT / 2 + 11, 'font-size': 9, fill: '#94a3b8',
            });
            roleText.textContent = resource.role || '—';
            svg.appendChild(roleText);

            // Hari libur pribadi (di belakang bar tugas).
            resource.holidays.forEach((holiday) => {
                const offset = daysBetween(data.project.start, holiday.date);
                if (offset < 0 || offset >= totalDays) return;

                svg.appendChild(svgElement('rect', {
                    x: NAME_WIDTH + offset * DAY_WIDTH, y: y + 2,
                    width: DAY_WIDTH, height: ROW_HEIGHT - 4,
                    fill: '#fecaca', opacity: 0.9,
                }));
            });

            // Bar alokasi tugas.
            resource.allocations.forEach((task) => {
                if (!task.start_date || !task.end_date) return;

                const offset = daysBetween(data.project.start, task.start_date);
                const span = Math.max(1, daysBetween(task.start_date, task.end_date) + 1);

                const bar = svgElement('rect', {
                    x: NAME_WIDTH + offset * DAY_WIDTH + 1,
                    y: y + 8,
                    width: Math.max(DAY_WIDTH - 2, span * DAY_WIDTH - 2),
                    height: ROW_HEIGHT - 16,
                    rx: 3,
                    fill: task.color || '#3b82f6',
                    opacity: 0.85,
                });
                const title = svgElement('title');
                title.textContent = `${resource.name} → ${task.name}\n${formatDate(task.start_date, fmt)} → ${formatDate(task.end_date, fmt)}`;
                bar.appendChild(title);
                svg.appendChild(bar);
            });

            if (!resource.allocations.length) {
                const none = svgElement('text', {
                    x: NAME_WIDTH + 8, y: y + ROW_HEIGHT / 2 + 4, 'font-size': 10, fill: '#cbd5e1',
                });
                none.textContent = 'belum dialokasikan';
                svg.appendChild(none);
            }
        });

        const legend = svgElement('text', { x: 10, y: height - 2, 'font-size': 10, fill: '#64748b' });
        legend.textContent = 'Merah muda = hari libur pribadi anggota';
        svg.appendChild(legend);

        container.appendChild(svg);
    }
}

function addDaysIso(iso, days) {
    const date = new Date(`${iso}T00:00:00`);
    date.setDate(date.getDate() + days);
    return date.toISOString().slice(0, 10);
}

function truncate(text, max) {
    return text.length > max ? `${text.slice(0, max - 1)}…` : text;
}
