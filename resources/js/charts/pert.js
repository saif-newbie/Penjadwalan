// PERT chart: node (tugas) + panah (dependensi) dengan penanda jalur kritis (SVG).
import { formatDate, svgElement, clearChildren } from './common.js';

const NODE_W = 140;
const NODE_H = 64;
const GAP_X = 100;
const GAP_Y = 30;
const PADDING = 24;

export function initPert(container) {
    const url = container.dataset.url;
    const toggle = document.getElementById('toggle-critical-pert');
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
            container.innerHTML = `<p class="text-red-600 text-sm">Gagal memuat PERT chart: ${err}</p>`;
        });

    function render() {
        if (!data.nodes.length) {
            container.innerHTML = '<p class="text-slate-500 text-sm p-4">Belum ada tugas untuk diagram PERT.</p>';
            return;
        }

        // Layout: kolom berdasarkan kedalaman topologis (longest-path layering).
        const depth = {};
        const byId = {};
        data.nodes.forEach((n) => { byId[n.id] = n; depth[n.id] = 0; });

        // Iterasi relaksasi sederhana (aman untuk graf berarah tanpa siklus).
        for (let pass = 0; pass < data.nodes.length; pass++) {
            let changed = false;
            data.edges.forEach((e) => {
                if (byId[e.from] && byId[e.to] && depth[e.to] < depth[e.from] + 1) {
                    depth[e.to] = depth[e.from] + 1;
                    changed = true;
                }
            });
            if (!changed) break;
        }

        const columns = {};
        data.nodes.forEach((n) => {
            const d = depth[n.id];
            (columns[d] = columns[d] || []).push(n);
        });

        const positions = {};
        const maxRows = Math.max(...Object.values(columns).map((c) => c.length));
        const width = PADDING * 2 + (Object.keys(columns).length) * (NODE_W + GAP_X);
        const height = PADDING * 2 + maxRows * (NODE_H + GAP_Y);

        Object.entries(columns).forEach(([d, nodes]) => {
            const colX = PADDING + Number(d) * (NODE_W + GAP_X);
            const colHeight = nodes.length * (NODE_H + GAP_Y) - GAP_Y;
            const startY = PADDING + (maxRows * (NODE_H + GAP_Y) - GAP_Y - colHeight) / 2;

            nodes.forEach((node, i) => {
                positions[node.id] = {
                    x: colX,
                    y: startY + i * (NODE_H + GAP_Y),
                };
            });
        });

        clearChildren(container);

        const svg = svgElement('svg', { width, height, viewBox: `0 0 ${width} ${height}`, class: 'block bg-white' });

        const marker = svgElement('marker', {
            id: 'pert-arrow', markerWidth: '9', markerHeight: '9', refX: '8', refY: '3', orient: 'auto',
        });
        marker.appendChild(svgElement('path', { d: 'M0,0 L0,6 L8,3 z', fill: '#475569' }));

        const markerCritical = svgElement('marker', {
            id: 'pert-arrow-critical', markerWidth: '9', markerHeight: '9', refX: '8', refY: '3', orient: 'auto',
        });
        markerCritical.appendChild(svgElement('path', { d: 'M0,0 L0,6 L8,3 z', fill: '#dc2626' }));

        const defs = svgElement('defs');
        defs.appendChild(marker);
        defs.appendChild(markerCritical);
        svg.appendChild(defs);

        // Panah di belakang node.
        data.edges.forEach((edge) => {
            const from = positions[edge.from];
            const to = positions[edge.to];
            if (!from || !to) return;

            const bothCritical = showCritical && byId[edge.from].is_critical && byId[edge.to].is_critical;
            const startX = from.x + NODE_W;
            const startY = from.y + NODE_H / 2;
            const endX = to.x;
            const endY = to.y + NODE_H / 2;
            const midX = startX + (endX - startX) / 2;

            const path = svgElement('path', {
                d: `M ${startX} ${startY} H ${midX} V ${endY} H ${endX - 6}`,
                fill: 'none',
                stroke: bothCritical ? '#dc2626' : '#475569',
                'stroke-width': bothCritical ? 2.2 : 1.4,
                'stroke-dasharray': bothCritical ? 'none' : '5 3',
                'marker-end': bothCritical ? 'url(#pert-arrow-critical)' : 'url(#pert-arrow)',
            });
            const title = svgElement('title');
            title.textContent = `${byId[edge.from].name} → ${byId[edge.to].name} (${edge.type}${edge.delay ? `, delay ${edge.delay}` : ''})`;
            path.appendChild(title);
            svg.appendChild(path);

            const label = svgElement('text', {
                x: midX, y: (startY + endY) / 2 - 5,
                'text-anchor': 'middle', 'font-size': 9,
                fill: bothCritical ? '#dc2626' : '#94a3b8',
            });
            label.textContent = `${edge.type}${edge.delay ? `+${edge.delay}` : ''}`;
            svg.appendChild(label);
        });

        // Node.
        data.nodes.forEach((node) => {
            const pos = positions[node.id];
            const critical = showCritical && node.is_critical;

            const group = svgElement('g');

            const rect = svgElement('rect', {
                x: pos.x, y: pos.y, width: NODE_W, height: NODE_H, rx: 8,
                fill: critical ? '#fef2f2' : '#f8fafc',
                stroke: critical ? '#dc2626' : node.color || '#3b82f6',
                'stroke-width': critical ? 2.5 : 1.5,
            });
            const title = svgElement('title');
            title.textContent = `${node.name}\nDurasi: ${node.duration} hari kerja\n${formatDate(node.start, data.project.date_format)} → ${formatDate(node.end, data.project.date_format)}${critical ? '\n★ Jalur kritis' : ''}`;
            rect.appendChild(title);
            group.appendChild(rect);

            const name = svgElement('text', {
                x: pos.x + NODE_W / 2, y: pos.y + 18,
                'text-anchor': 'middle', 'font-size': 11, 'font-weight': 700, fill: '#1e293b',
            });
            name.textContent = truncate(node.name, 18);
            group.appendChild(name);

            const dur = svgElement('text', {
                x: pos.x + NODE_W / 2, y: pos.y + 34,
                'text-anchor': 'middle', 'font-size': 10, fill: '#475569',
            });
            dur.textContent = `${node.duration} hari`;
            group.appendChild(dur);

            const dates = svgElement('text', {
                x: pos.x + NODE_W / 2, y: pos.y + 48,
                'text-anchor': 'middle', 'font-size': 8.5, fill: '#94a3b8',
            });
            dates.textContent = node.start
                ? `${formatDate(node.start, data.project.date_format)} → ${formatDate(node.end, data.project.date_format)}`
                : '—';
            group.appendChild(dates);

            if (critical) {
                const star = svgElement('text', {
                    x: pos.x + NODE_W - 12, y: pos.y + 14, 'font-size': 11, fill: '#dc2626',
                });
                star.textContent = '★';
                group.appendChild(star);
            }

            svg.appendChild(group);
        });

        container.appendChild(svg);
    }
}

function truncate(text, max) {
    return text.length > max ? `${text.slice(0, max - 1)}…` : text;
}
