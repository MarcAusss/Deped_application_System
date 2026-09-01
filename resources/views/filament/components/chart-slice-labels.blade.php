<script>
    (function () {
        window.filamentChartJsPlugins = window.filamentChartJsPlugins || [];

        if (window.filamentChartJsPlugins.some((plugin) => plugin.id === 'sliceLabels')) {
            return;
        }

        window.filamentChartJsPlugins.push({
            id: 'sliceLabels',
            afterDraw(chart) {
                if (!['pie', 'doughnut'].includes(chart.config.type)) {
                    return;
                }

                const { ctx } = chart;

                chart.data.datasets.forEach((dataset, datasetIndex) => {
                    const meta = chart.getDatasetMeta(datasetIndex);

                    if (meta.hidden) {
                        return;
                    }

                    const total = dataset.data.reduce((sum, value, index) => {
                        return meta.data[index] && !meta.data[index].hidden
                            ? sum + Number(value || 0)
                            : sum;
                    }, 0);

                    meta.data.forEach((element, index) => {
                        if (element.hidden) {
                            return;
                        }

                        const value = Number(dataset.data[index] || 0);

                        if (value <= 0 || total <= 0) {
                            return;
                        }

                        const percentage = Math.round((value / total) * 100);

                        if (percentage <= 0) {
                            return;
                        }

                        const { x, y } = element.tooltipPosition();

                        ctx.save();
                        ctx.fillStyle = '#ffffff';
                        ctx.font = 'bold 12px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.shadowColor = 'rgba(0, 0, 0, 0.35)';
                        ctx.shadowBlur = 3;
                        ctx.fillText(percentage + '%', x, y);
                        ctx.restore();
                    });
                });
            },
        });
    })();
</script>
