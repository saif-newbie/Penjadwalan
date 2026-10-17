// Gantt chart: daftar tugas (kiri) + timeline bar + panah dependensi (SVG).
import { formatDate, daysBetween, svgElement, clearChildren } from './common.js';

const ROW_HEIGHT = 30;
const HEADER_HEIGHT = 36;
const NAME_WIDTH = 240;
const DAY_WIDTH = 20;
const MIN_TIMELINE = 560;

export function initGantt(container) {
    const url = container.dataset.url;
    const toggle = document.getElementById('toggle-critical');
    let showCritical = true;
    let data = null;

    fetch(url)
        .then((res) => res.json())
        .then((json) => {
            data = json;
            render();

            if (toggle) {
                toggle.addEventListener('click', () => {
                    showCritical = !showCritical;
                    toggle.textContent = showCritical ? 'Hide Critical Path' : 'Show Critical Path';
                    render();
                });
            }
        })
        .catch((err) => {
            container.innerHTML = `<p class="text-red-600 text-sm">Gagal memuat data Gantt: ${err}</p>`;
        });

    function isNonWorking(iso) {
        const date = new Date(`${iso}T00:00:00`);
        const day = date.getDay();

        if (data.project.weekend_mode === 'off' && (day === 0 || day === 6)) {
            return true;
        }

        return data.holidays.some((h) => {
            if (h.type === 'recurring') {
                return date.getMonth() + 1 === Number(h.month) && date.getDate() === Number(h.day);
            }
            return h.date === iso;
        });
    }

    function render() {
        if (!data.tasks.length) {
            container.innerHTML = '<p class="text-slate-500 text-sm p-4">Belum ada tugas. Tambahkan tugas di tab Tugas &amp; Dependensi.</p>';
            return;
        }

        const fmt = data.project.date_format;
        const start = data.project.start;
        const totalDays = Math.max(1, daysBetween(start, data.project.end) + 1);
        const timelineWidth = Math.max(MIN_TIMELINE, totalDays * DAY_WIDTH);
        const width = NAME_WIDTH + timelineWidth + 20;
        const height = HEADER_HEIGHT + data.tasks.length * ROW_HEIGHT + 12;

        clearChildren(container);

        const svg = svgElement('svg', {
            width,
            height,
            viewBox: `0 0 ${width} ${height}`,
            class: 'block bg-white',
        });

        const marker = svgElement('marker', {
            id: 'gantt-arrow',
            markerWidth: '8',
            markerHeight: '8',
            refX: '7',
            refY: '3',
            orient: 'auto',
        });
        marker.appendChild(svgElement('path', { d: 'M0,0 L0,6 L8,3 z', fill: '#475569' }));
        const defs = svgElement('defs');
        defs.appendChild(marker);
        svg.appendChild(defs);

        // Header tanggal.
        for (let d = 0; d < totalDays; d++) {
            const iso = addDaysIso(start, d);
            const x = NAME_WIDTH + d * DAY_WIDTH;
            const weekend = isNonWorking(iso);

            svg.appendChild(svgElement('rect', {
                x, y: 0, width: DAY_WIDTH, height: HEADER_HEIGHT,
                fill: weekend ? '#e2e8f0' : '#f8fafc',
                stroke: '#cbd5e1', 'stroke-width': 0.5,
            }));

            const date = new Date(`${iso}T00:00:00`);
            const label = svgElement('text', {
                x: x + DAY_WIDTH / 2, y: 14,
                'text-anchor': 'middle', 'font-size': 9, fill: '#64748b',
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

            // Kolom non-kerja di area baris tugas.
            if (weekend) {
                svg.appendChild(svgElement('rect', {
                    x, y: HEADER_HEIGHT, width: DAY_WIDTH,
                    height: data.tasks.length * ROW_HEIGHT,
                    fill: '#f1f5f9', opacity: 0.8,
                }));
            }
        }

        // Baris tugas.
        data.tasks.forEach((task, i) => {
            const y = HEADER_HEIGHT + i * ROW_HEIGHT;
            const critical = showCritical && task.is_critical;

            svg.appendChild(svgElement('rect', {
                x: 0, y, width: NAME_WIDTH, height: ROW_HEIGHT,
                fill: i % 2 ? '#f8fafc' : '#ffffff', stroke: '#e2e8f0', 'stroke-width': 0.5,
            }));
            svg.appendChild(svgElement('rect', {
                x: NAME_WIDTH, y, width: timelineWidth, height: ROW_HEIGHT,
                fill: i % 2 ? '#fcfcfd' : '#ffffff', stroke: '#f1f5f9', 'stroke-width': 0.5,
            }));

            const nameText = svgElement('text', {
                x: 10, y: y + ROW_HEIGHT / 2 + 4,
                'font-size': 12,
                'font-weight': task.is_summary ? 700 : 500,
                fill: '#1e293b',
            });
            nameText.textContent = (task.is_summary ? '▸ ' : '') + truncate(task.name, 26);
            nameText.appendChild(svgElement('title')).textContent = task.name;
            svg.appendChild(nameText);

            const durText = svgElement('text', {
                x: NAME_WIDTH - 8, y: y + ROW_HEIGHT / 2 + 4,
                'text-anchor': 'end', 'font-size': 10, fill: '#94a3b8',
            });
            durText.textContent = `${task.duration}h`;
            svg.appendChild(durText);

            const offset = daysBetween(start, task.start_date);
            const span = Math.max(1, daysBetween(task.start_date, task.end_date) + 1);
            const bar = svgElement('rect', {
                x: NAME_WIDTH + offset * DAY_WIDTH + 1,
                y: y + 6,
                width: Math.max(DAY_WIDTH - 2, span * DAY_WIDTH - 2),
                height: ROW_HEIGHT - 12,
                rx: 4,
                fill: task.color || '#3b82f6',
                stroke: critical ? '#dc2626' : 'none',
                'stroke-width': critical ? 2 : 0,
            });
            const title = svgElement('title');
            title.textContent = `${task.name}\n${formatDate(task.start_date, fmt)} → ${formatDate(task.end_date, fmt)} (${task.duration} hari kerja)${critical ? '\n★ Jalur kritis' : ''}`;
            bar.appendChild(title);
            svg.appendChild(bar);
        });

        // Panah dependensi.
        const rowOf = {};
        data.tasks.forEach((task, i) => { rowOf[task.id] = i; });

        const arrows = svgElement('g', { fill: 'none', 'stroke-width': 1.5 });
        data.dependencies.forEach((dep) => {
            const pi = rowOf[dep.predecessor_id];
            const si = rowOf[dep.successor_id];
            if (pi === undefined || si === undefined) return;

            const pred = data.tasks[pi];
            const succ = data.tasks[si];
            const bothCritical = showCritical && pred.is_critical && succ.is_critical;

            const predOffset = daysBetween(start, pred.start_date);
            const predSpan = Math.max(1, daysBetween(pred.start_date, pred.end_date) + 1);
            const succOffset = daysBetween(start, succ.start_date);
            const succSpan = Math.max(1, daysBetween(succ.start_date, succ.end_date) + 1);

            const px = NAME_WIDTH + (['FS', 'FF'].includes(dep.type) ? predOffset + predSpan : predOffset) * DAY_WIDTH;
            const py = HEADER_HEIGHT + pi * ROW_HEIGHT + ROW_HEIGHT / 2;
            const sx = NAME_WIDTH + (['SF', 'FF'].includes(dep.type) ? succOffset + succSpan : succOffset) * DAY_WIDTH;
            const sy = HEADER_HEIGHT + si * ROW_HEIGHT + ROW_HEIGHT / 2;

            // Jalur siku: keluar sedikit dari bar sumber, turun/menaik, masuk ke bar tujuan.
            const stub = 8;
            const dirX = sx >= px ? 1 : -1;
            const midX = px + stub * dirX;
            const d = `M ${px} ${py} H ${midX} V ${sy} H ${sx - 4 * dirX * -1}`;

            const path = svgElement('path', {
                d,
                stroke: bothCritical ? '#dc2626' : '#475569',
                'stroke-dasharray': bothCritical ? 'none' : '4 2',
                'marker-end': 'url(#gantt-arrow)',
                opacity: showCritical && !bothCritical && (pred.is_critical || succ.is_critical) ? 0.35 : 1,
            });
            const title = svgElement('title');
            title.textContent = `${pred.name} → ${succ.name} (${dep.type}${dep.delay ? `, delay ${dep.delay}` : ''})`;
            path.appendChild(title);
            arrows.appendChild(path);
        });
        svg.appendChild(arrows);

        // Legenda jalur kritis.
        if (showCritical && data.tasks.some((t) => t.is_critical)) {
            const legend = svgElement('text', {
                x: 10, y: height - 2, 'font-size': 10, fill: '#dc2626',
            });
            legend.textContent = '★ Merah tebal = jalur kritis';
            svg.appendChild(legend);
        }

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
