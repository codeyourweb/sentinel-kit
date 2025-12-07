import { computed } from 'vue'

export function useChartTimeUtils() {
    const getAvailableIntervals = (startTime, endTime) => {
        const start = new Date(startTime)
        const end = new Date(endTime)
        const diffMs = end - start
        const diffHours = diffMs / (1000 * 60 * 60)
        const diffDays = diffHours / 24

        const allIntervals = [
            { value: '1s', label: '1 second', intervalType: 'fixed_interval', unit: 'second', displayFormat: { second: 'HH:mm:ss' }, tooltipFormat: 'MMM dd, HH:mm:ss' },
            { value: '30s', label: '30 seconds', intervalType: 'fixed_interval', unit: 'second', displayFormat: { second: 'HH:mm:ss' }, tooltipFormat: 'MMM dd, HH:mm:ss' },
            { value: '1m', label: '1 minute', intervalType: 'fixed_interval', unit: 'minute', displayFormat: { minute: 'HH:mm' }, tooltipFormat: 'MMM dd, HH:mm' },
            { value: '5m', label: '5 minutes', intervalType: 'fixed_interval', unit: 'minute', displayFormat: { minute: 'HH:mm' }, tooltipFormat: 'MMM dd, HH:mm' },
            { value: '15m', label: '15 minutes', intervalType: 'fixed_interval', unit: 'minute', displayFormat: { minute: 'HH:mm' }, tooltipFormat: 'MMM dd, HH:mm' },
            { value: '30m', label: '30 minutes', intervalType: 'fixed_interval', unit: 'minute', displayFormat: { minute: 'HH:mm' }, tooltipFormat: 'MMM dd, HH:mm' },
            { value: '1h', label: '1 hour', intervalType: 'calendar_interval', unit: 'hour', displayFormat: { hour: 'HH:mm' }, tooltipFormat: 'MMM dd, HH:mm' },
            { value: '3h', label: '3 hours', intervalType: 'fixed_interval', unit: 'hour', displayFormat: { hour: 'MMM dd HH:mm' }, tooltipFormat: 'MMM dd, HH:mm' },
            { value: '6h', label: '6 hours', intervalType: 'fixed_interval', unit: 'hour', displayFormat: { hour: 'MMM dd HH:mm' }, tooltipFormat: 'MMM dd, HH:mm' },
            { value: '12h', label: '12 hours', intervalType: 'fixed_interval', unit: 'hour', displayFormat: { hour: 'MMM dd HH:mm' }, tooltipFormat: 'MMM dd, HH:mm' },
            { value: '1d', label: '1 day', intervalType: 'calendar_interval', unit: 'day', displayFormat: { day: 'MMM dd' }, tooltipFormat: 'MMM dd, yyyy' },
            { value: '3d', label: '3 days', intervalType: 'fixed_interval', unit: 'day', displayFormat: { day: 'MMM dd' }, tooltipFormat: 'MMM dd, yyyy' },
            { value: '1w', label: '1 week', intervalType: 'calendar_interval', unit: 'week', displayFormat: { week: 'MMM dd' }, tooltipFormat: 'Week of MMM dd, yyyy' },
            { value: '1M', label: '1 month', intervalType: 'calendar_interval', unit: 'month', displayFormat: { month: 'MMM yyyy' }, tooltipFormat: 'MMM yyyy' }
        ]

        // Filter intervals based on time range to show only reasonable options
        if (diffDays <= 1) {
            return allIntervals.filter(i => ['1s', '30s', '1m', '5m', '15m', '30m', '1h', '3h', '6h'].includes(i.value))
        } else if (diffDays <= 3) {
            return allIntervals.filter(i => ['30s', '1m', '5m', '15m', '30m', '1h', '3h', '6h', '12h'].includes(i.value))
        } else if (diffDays <= 7) {
            return allIntervals.filter(i => ['1m', '5m', '15m', '30m', '1h', '3h', '6h', '12h', '1d'].includes(i.value))
        } else if (diffDays <= 30) {
            return allIntervals.filter(i => ['5m', '15m', '30m', '1h', '3h', '6h', '12h', '1d', '3d'].includes(i.value))
        } else if (diffDays <= 90) {
            return allIntervals.filter(i => ['30m', '1h', '3h', '6h', '12h', '1d', '3d', '1w'].includes(i.value))
        } else {
            return allIntervals.filter(i => ['1h', '3h', '6h', '12h', '1d', '3d', '1w', '1M'].includes(i.value))
        }
    }

    const getTimeInterval = (startTime, endTime, customInterval = null) => {
        const intervals = getAvailableIntervals(startTime, endTime)
        
        // If a custom interval is provided and it's valid for this time range, use it
        if (customInterval) {
            const customConfig = intervals.find(i => i.value === customInterval)
            if (customConfig) {
                return {
                    interval: customConfig.value,
                    intervalType: customConfig.intervalType,
                    unit: customConfig.unit,
                    displayFormat: customConfig.displayFormat,
                    tooltipFormat: customConfig.tooltipFormat
                }
            }
        }

        // Default behavior - auto-select based on time range
        const start = new Date(startTime)
        const end = new Date(endTime)
        const diffMs = end - start
        const diffHours = diffMs / (1000 * 60 * 60)
        const diffDays = diffHours / 24

        if (diffDays <= 1) {
            return {
                interval: '1h',
                intervalType: 'calendar_interval',
                unit: 'hour',
                displayFormat: { hour: 'HH:mm' },
                tooltipFormat: 'MMM dd, HH:mm'
            }
        } else if (diffDays <= 3) {
            return {
                interval: '3h',
                intervalType: 'fixed_interval',
                unit: 'hour',
                displayFormat: { hour: 'MMM dd HH:mm' },
                tooltipFormat: 'MMM dd, HH:mm'
            }
        } else if (diffDays <= 7) {
            return {
                interval: '6h',
                intervalType: 'fixed_interval',
                unit: 'hour',
                displayFormat: { hour: 'MMM dd HH:mm' },
                tooltipFormat: 'MMM dd, HH:mm'
            }
        } else if (diffDays <= 30) {
            return {
                interval: '1d',
                intervalType: 'calendar_interval',
                unit: 'day',
                displayFormat: { day: 'MMM dd' },
                tooltipFormat: 'MMM dd, yyyy'
            }
        } else if (diffDays <= 90) {
            return {
                interval: '3d',
                intervalType: 'fixed_interval',
                unit: 'day',
                displayFormat: { day: 'MMM dd' },
                tooltipFormat: 'MMM dd, yyyy'
            }
        } else {
            return {
                interval: '1w',
                intervalType: 'calendar_interval',
                unit: 'week',
                displayFormat: { week: 'MMM dd' },
                tooltipFormat: 'Week of MMM dd, yyyy'
            }
        }
    }

    const getCurrentInterval = (startDate, endDate, customInterval = null) => {
        const startTime = new Date(startDate).toISOString()
        const endTime = new Date(endDate).toISOString()
        const config = getTimeInterval(startTime, endTime, customInterval)
        
        const intervalMap = {
            '1s': '1 second',
            '30s': '30 seconds',
            '1m': '1 minute',
            '5m': '5 minutes',
            '15m': '15 minutes',
            '30m': '30 minutes',
            '1h': '1 hour',
            '3h': '3 hours',
            '6h': '6 hours',
            '12h': '12 hours',
            '1d': '1 day',
            '3d': '3 days',
            '1w': '1 week',
            '1M': '1 month'
        }
        
        return intervalMap[config.interval] || config.interval
    }

    const createChartOptions = (startDate, endDate, customInterval = null) => {
        const timeConfig = getTimeInterval(startDate, endDate, customInterval)
        
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                title: {
                    display: false
                },
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            try {
                                const dataPoint = context[0]
                                const timestamp = dataPoint.parsed ? dataPoint.parsed.x : dataPoint.label
                                const date = new Date(timestamp)
                                
                                if (isNaN(date.getTime())) {
                                    console.error('Invalid timestamp:', timestamp)
                                    return 'Invalid date'
                                }
                                
                                return date.toLocaleDateString('en-US', {
                                    month: 'short',
                                    day: 'numeric',
                                    year: 'numeric',
                                    hour: timeConfig.unit === 'hour' ? 'numeric' : undefined,
                                    minute: timeConfig.unit === 'hour' ? '2-digit' : undefined
                                })
                            } catch (error) {
                                console.error('Tooltip error:', error)
                                return 'Date error'
                            }
                        }
                    }
                },
                zoom: {
                    zoom: {
                        wheel: { enabled: false },
                        pinch: { enabled: false },
                        mode: 'x'
                    },
                    pan: {
                        enabled: true,
                        mode: 'x',
                        modifierKey: 'ctrl'
                    },
                    limits: {
                        x: {
                            min: 'original',
                            max: 'original'
                        }
                    }
                }
            },
            onHover: (event, elements, chart) => {
                chart.canvas.style.cursor = elements.length > 0 ? 'pointer' : 'crosshair'
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: timeConfig.unit,
                        displayFormats: timeConfig.displayFormat
                    },
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Count'
                    }
                }
            }
        }
    }

    return {
        getTimeInterval,
        getCurrentInterval,
        createChartOptions,
        getAvailableIntervals
    }
}