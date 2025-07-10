<div>
    <canvas id="heatmap" width="800" height="400"></canvas>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-chart-matrix"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const users = ['Alice','Bob','Carol','David','Jenny','Servalan','Jim'];
        const dates = [];
        {
            const today = new Date();
            for(let i = 0; i < 60; i++){
                const dd = new Date(today.getFullYear(), today.getMonth(), today.getDate() + i);
                if (dd.getDay() === 0 || dd.getDay() === 6) continue;
                dates.push(dd.toISOString().slice(0,10));
            }
        }
        const raw = [];
        // Generate one value per user per date (using names as Y labels)
        users.forEach((user) => {
            dates.forEach((dt) => {
                raw.push({ x: dt, y: user, v: Math.floor(Math.random() * 12) });
            });
        });

        const ctx = document.getElementById('heatmap').getContext('2d');
        new Chart(ctx, {
            type: 'matrix',
            data: {
                datasets: [{
                    label: 'Requests/day',
                    data: raw,
                    // Yellow -> Orange -> Red gradient
                    backgroundColor(ctx) {
                        const { v } = ctx.dataset.data[ctx.dataIndex];
                        const pct = Math.min(v / 11, 1); // Adjust '11' if your max value changes
                        let r, g, b;
                        if (pct < 0.5) {
                            // yellow (#ffff00) to orange (#ffa500)
                            const local = pct / 0.5;
                            r = 255;
                            g = 255 - Math.round(85 * local); // 255 -> 170
                            b = 0;
                        } else {
                            // orange (#ffa500) to red (#ff0000)
                            const local = (pct - 0.5) / 0.5;
                            r = 255;
                            g = 170 - Math.round(170 * local); // 170 -> 0
                            b = 0;
                        }
                        return `rgba(${r},${g},${b},0.8)`;
                    },
                    width: ({ chart }) => {
                        const ca = chart.chartArea;
                        return ca ? (ca.width / dates.length) - 1 : 10;
                    },
                    height: ({ chart }) => {
                        const ca = chart.chartArea;
                        return ca ? (ca.height / users.length) - 1 : 30;
                    },
                }]
            },
            options: {
                scales: {
                    x: {
                        type: 'category',
                        labels: dates,
                        offset: true,
                        grid: { display: false },
                        ticks: { maxRotation: 90, autoSkip: true, maxTicksLimit: 15 }
                    },
                    y: {
                        type: 'category',
                        labels: users,
                        offset: true,
                        grid: { display: false },
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            title: ([{ raw }]) => `${raw.y} on ${raw.x}`,
                            label: ({ raw }) => `${raw.v} requests`
                        }
                    }
                }
            }
        });
    });
</script>
@endpush
</div>
