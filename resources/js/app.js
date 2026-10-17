// Mini GanttProject — entry point Vite.
import Alpine from 'alpinejs';
import { initGantt } from './charts/gantt.js';
import { initResourceChart } from './charts/resource.js';
import { initPert } from './charts/pert.js';

window.Alpine = Alpine;
Alpine.start();

// Chart digambar saat DOM siap dan tab-nya terlihat.
document.addEventListener('DOMContentLoaded', () => {
    const ganttEl = document.getElementById('gantt-chart');
    if (ganttEl) {
        initGantt(ganttEl);
    }

    const resourceEl = document.getElementById('resource-chart');
    if (resourceEl) {
        initResourceChart(resourceEl);
    }

    const pertEl = document.getElementById('pert-chart');
    if (pertEl) {
        initPert(pertEl);
    }
});
