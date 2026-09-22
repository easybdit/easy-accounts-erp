<script setup>
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
} from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, BarElement, CategoryScale, LinearScale);

const props = defineProps({
    trend: {
        type: Array,
        required: true,
    },
});

const chartData = computed(() => ({
    labels: props.trend.map((row) => row.month),
    datasets: [
        {
            label: 'Income',
            data: props.trend.map((row) => parseFloat(row.income)),
            backgroundColor: '#22c55e',
            borderRadius: 4,
            maxBarThickness: 32,
        },
        {
            label: 'Expense',
            data: props.trend.map((row) => parseFloat(row.expense)),
            backgroundColor: '#ef4444',
            borderRadius: 4,
            maxBarThickness: 32,
        },
    ],
}));

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            align: 'end',
            labels: {
                boxWidth: 10,
                boxHeight: 10,
                usePointStyle: true,
                pointStyle: 'circle',
                font: { size: 12 },
                color: '#6b7280',
            },
        },
        tooltip: {
            callbacks: {
                label: (context) => `${context.dataset.label}: ${context.formattedValue}`,
            },
        },
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#6b7280', font: { size: 12 } },
        },
        y: {
            beginAtZero: true,
            grid: { color: '#f3f4f6' },
            ticks: { color: '#6b7280', font: { size: 12 } },
        },
    },
};
</script>

<template>
    <div class="h-72">
        <Bar :data="chartData" :options="chartOptions" />
    </div>
</template>
