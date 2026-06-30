<script setup>
import { computed } from 'vue';
import VueApexCharts from 'vue3-apexcharts';

const props = defineProps({
    debts:   { type: Number, default: 0 },
    capital: { type: Number, default: 0 },
    goals:   { type: Number, default: 0 },
});

// Only render when there is actual data to show.
const hasData = computed(() => props.debts + props.capital + props.goals > 0);

const series = computed(() => [
    props.debts   > 0 ? props.debts   : 0.001,   // prevent "empty" donut
    props.capital > 0 ? props.capital : 0.001,
    props.goals   > 0 ? props.goals   : 0.001,
]);

const chartOptions = computed(() => ({
    chart: {
        type: 'donut',
        background: 'transparent',
        animations: {
            enabled: true,
            speed: 800,
            animateGradually: { enabled: true, delay: 100 },
            dynamicAnimation: { enabled: true, speed: 350 },
        },
        toolbar: { show: false },
    },
    labels: ['HP Enemigo', 'Capital Libre', 'Recursos Forja'],
    colors: [
        '#ef4444',   // neon red  — debts
        '#10b981',   // neon green — capital
        '#3b82f6',   // neon blue  — goals
    ],
    dataLabels: { enabled: false },
    legend: {
        show: true,
        position: 'bottom',
        labels: { colors: '#94a3b8' },
        fontFamily: 'ui-monospace, monospace',
        fontSize: '11px',
        fontWeight: 700,
        itemMargin: { horizontal: 10, vertical: 4 },
        markers: { width: 8, height: 8, radius: 2 },
    },
    stroke: { width: 0 },
    plotOptions: {
        pie: {
            donut: {
                size: '72%',
                labels: {
                    show: true,
                    name: {
                        show: true,
                        color: '#64748b',
                        fontFamily: 'ui-monospace, monospace',
                        fontSize: '11px',
                        fontWeight: 700,
                        offsetY: -4,
                    },
                    value: {
                        show: true,
                        color: '#f8fafc',
                        fontFamily: 'ui-monospace, monospace',
                        fontSize: '14px',
                        fontWeight: 900,
                        formatter: (v) =>
                            'RD$ ' + Number(v).toLocaleString('es-DO', { maximumFractionDigits: 0 }),
                    },
                    total: {
                        show: true,
                        label: 'BALANCE',
                        color: '#475569',
                        fontFamily: 'ui-monospace, monospace',
                        fontSize: '10px',
                        fontWeight: 700,
                        formatter: () => 'TOTAL',
                    },
                },
            },
        },
    },
    tooltip: {
        theme: 'dark',
        style: {
            fontFamily: 'ui-monospace, monospace',
            fontSize: '12px',
        },
        y: {
            formatter: (v) =>
                'RD$ ' + Number(v).toLocaleString('es-DO', { maximumFractionDigits: 2 }),
        },
    },
    grid: { show: false },
    states: {
        hover:  { filter: { type: 'lighten', value: 0.05 } },
        active: { filter: { type: 'darken',  value: 0.35 } },
    },
}));
</script>

<template>
    <div v-if="hasData" class="flex flex-col items-center justify-center w-full">
        <VueApexCharts
            type="donut"
            :options="chartOptions"
            :series="series"
            width="100%"
            height="260"
        />
    </div>
    <div v-else class="flex flex-col items-center justify-center py-10 gap-2">
        <span class="text-3xl opacity-30">📡</span>
        <p class="text-slate-600 text-xs font-bold uppercase tracking-widest">Sin datos de combate</p>
    </div>
</template>
