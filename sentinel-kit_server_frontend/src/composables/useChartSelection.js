import { ref, computed } from 'vue'
import {
    Chart as ChartJS
} from 'chart.js'

export function useChartSelection(chartRef, emit) {
    const isSelecting = ref(false)
    const selectionStart = ref(null)

    const selectionOverlayStyle = computed(() => {
        if (!isSelecting.value || !selectionStart.value) return {}
        
        const start = Math.min(selectionStart.value.x, selectionStart.value.currentX)
        const width = Math.abs(selectionStart.value.currentX - selectionStart.value.x)
        
        return {
            left: `${start}px`,
            width: `${width}px`,
            height: '100%',
            backgroundColor: 'rgba(249, 115, 22, 0.2)', 
            border: '2px solid rgba(249, 115, 22, 0.6)'
        }
    })

    const formatDateTimeLocal = (date) => {
        const year = date.getFullYear()
        const month = String(date.getMonth() + 1).padStart(2, '0')
        const day = String(date.getDate()).padStart(2, '0')
        const hours = String(date.getHours()).padStart(2, '0')
        const minutes = String(date.getMinutes()).padStart(2, '0')
        
        return `${year}-${month}-${day}T${hours}:${minutes}`
    }

    const getTimeFromCanvasX = (x, canvasRect) => {
        const chart = chartRef.value?.chart
        if (!chart) return new Date().toISOString()
        
        try {
            const chartArea = chart.chartArea
            if (!chartArea) return new Date().toISOString()
            
            const clampedX = Math.max(chartArea.left, Math.min(chartArea.right, x))
            const dataX = chart.scales.x.getValueForPixel(clampedX)
            const timeValue = new Date(dataX)
            
            return timeValue.toISOString()
        } catch (error) {
            return new Date().toISOString()
        }
    }

    const setupChartSelection = () => {
        let canvas = null
        
        if (chartRef.value?.chart?.canvas) {
            canvas = chartRef.value.chart.canvas
        } else if (chartRef.value?.$el) {
            canvas = chartRef.value.$el.querySelector('canvas')
        } else {
            canvas = document.querySelector('.h-64 canvas')
        }
        
        if (!canvas) return
        
        let startX = null
        let isMouseDown = false
        
        const onMouseDown = (event) => {
            event.preventDefault()
            const rect = canvas.getBoundingClientRect()
            const chart = chartRef.value?.chart
            
            if (!chart?.chartArea) return
            
            startX = event.clientX - rect.left
            
            if (startX >= chart.chartArea.left && startX <= chart.chartArea.right) {
                isMouseDown = true
                isSelecting.value = true
                selectionStart.value = {
                    x: startX,
                    currentX: startX,
                    time: getTimeFromCanvasX(startX, rect)
                }
                canvas.style.cursor = 'col-resize'
            }
        }
        
        const onMouseMove = (event) => {
            if (!isMouseDown || !selectionStart.value) {
                const chart = chartRef.value?.chart
                if (chart?.chartArea) {
                    const rect = canvas.getBoundingClientRect()
                    const x = event.clientX - rect.left
                    if (x >= chart.chartArea.left && x <= chart.chartArea.right) {
                        canvas.style.cursor = 'crosshair'
                    } else {
                        canvas.style.cursor = 'default'
                    }
                }
                return
            }
            
            const rect = canvas.getBoundingClientRect()
            const currentX = event.clientX - rect.left
            selectionStart.value.currentX = currentX
        }
        
        const onMouseUp = (event) => {
            if (!isMouseDown || !selectionStart.value) return
            
            const rect = canvas.getBoundingClientRect()
            const endX = event.clientX - rect.left
            
            const selectionWidth = Math.abs(endX - selectionStart.value.x)
            
            if (selectionWidth > 30) {
                const startTime = selectionStart.value.time
                const endTime = getTimeFromCanvasX(endX, rect)
                
                const newStartDate = new Date(Math.min(new Date(startTime), new Date(endTime)))
                const newEndDate = new Date(Math.max(new Date(startTime), new Date(endTime)))
                
                if (!isNaN(newStartDate.getTime()) && !isNaN(newEndDate.getTime())) {
                    emit('date-range-selected', {
                        startDate: formatDateTimeLocal(newStartDate),
                        endDate: formatDateTimeLocal(newEndDate)
                    })
                }
            }
            
            isMouseDown = false
            isSelecting.value = false
            selectionStart.value = null
            canvas.style.cursor = 'crosshair'
        }
        
        const onMouseLeave = () => {
            isMouseDown = false
            isSelecting.value = false
            selectionStart.value = null
        }
        
        // Remove existing listeners
        canvas.removeEventListener('mousedown', onMouseDown)
        canvas.removeEventListener('mousemove', onMouseMove)
        canvas.removeEventListener('mouseup', onMouseUp)
        canvas.removeEventListener('mouseleave', onMouseLeave)
        
        // Add new listeners
        canvas.addEventListener('mousedown', onMouseDown)
        canvas.addEventListener('mousemove', onMouseMove)
        canvas.addEventListener('mouseup', onMouseUp)
        canvas.addEventListener('mouseleave', onMouseLeave)
    }

    return {
        isSelecting,
        selectionOverlayStyle,
        setupChartSelection
    }
}