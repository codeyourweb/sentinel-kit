export function useAlertUtils() {
    const formatDateTime = (timestamp) => {
        try {
            const date = new Date(timestamp)
            if (isNaN(date.getTime())) return 'Invalid date'
            return date.toLocaleString()
        } catch (error) {
            return 'Invalid date'
        }
    }

    const formatSeverity = (severity) => {
        if (!severity) return 'unknown'
        return severity.toString().toLowerCase()
    }

    const getSeverityClass = (severity) => {
        const sev = formatSeverity(severity)
        
        switch (sev) {
            case 'critical':
            case '1':
                return 'bg-red-100 text-red-800 border-red-300'
            case 'high':
            case '2':
                return 'bg-orange-100 text-orange-800 border-orange-300'
            case 'medium':
            case '3':
                return 'bg-yellow-100 text-yellow-800 border-yellow-300'
            case 'low':
            case '4':
                return 'bg-green-100 text-green-800 border-green-300'
            default:
                return 'bg-gray-100 text-gray-800 border-gray-300'
        }
    }

    const formatRuleName = (ruleName) => {
        if (!ruleName) return 'Unknown Rule'
        
        // Remove file extension if present
        const withoutExt = ruleName.replace(/\.(ya?ml|json)$/i, '')
        
        // Replace underscores and hyphens with spaces
        const withSpaces = withoutExt.replace(/[_-]/g, ' ')
        
        // Capitalize each word
        const capitalized = withSpaces.replace(/\b\w/g, l => l.toUpperCase())
        
        return capitalized
    }

    const formatFieldValue = (value) => {
        if (value === null || value === undefined) {
            return 'N/A'
        }
        
        if (typeof value === 'boolean') {
            return value ? 'Yes' : 'No'
        }
        
        if (typeof value === 'object') {
            if (Array.isArray(value)) {
                return value.length > 0 ? value.join(', ') : 'Empty array'
            }
            return JSON.stringify(value, null, 2)
        }
        
        if (typeof value === 'string' && value.trim() === '') {
            return 'Empty'
        }
        
        return String(value)
    }

    const isImportantField = (key) => {
        const importantFields = [
            '@timestamp',
            'severity',
            'rule_name',
            'title',
            'description',
            'source_ip',
            'destination_ip',
            'user',
            'hostname',
            'process_name',
            'command_line',
            'file_path',
            'registry_key',
            'network_protocol',
            'event_id',
            'status',
            'action'
        ]
        
        return importantFields.includes(key.toLowerCase())
    }

    const shouldDisplayField = (key, value) => {
        // Skip metadata fields
        const skipFields = ['_index', '_type', '_id', '_score', '_source']
        if (skipFields.includes(key)) return false
        
        // Skip empty values unless they're important fields
        if ((value === null || value === undefined || value === '') && !isImportantField(key)) {
            return false
        }
        
        return true
    }

    const sortAlertFields = (alertData) => {
        const entries = Object.entries(alertData)
        
        // Separate important fields from others
        const importantEntries = entries.filter(([key]) => isImportantField(key))
        const otherEntries = entries.filter(([key]) => !isImportantField(key))
        
        // Sort important fields by predefined order
        const importantOrder = [
            '@timestamp', 'severity', 'rule_name', 'title', 'description',
            'source_ip', 'destination_ip', 'user', 'hostname', 'process_name'
        ]
        
        importantEntries.sort(([a], [b]) => {
            const indexA = importantOrder.indexOf(a.toLowerCase())
            const indexB = importantOrder.indexOf(b.toLowerCase())
            
            if (indexA === -1 && indexB === -1) return a.localeCompare(b)
            if (indexA === -1) return 1
            if (indexB === -1) return -1
            return indexA - indexB
        })
        
        // Sort other fields alphabetically
        otherEntries.sort(([a], [b]) => a.localeCompare(b))
        
        return [...importantEntries, ...otherEntries]
    }

    const getDisplayableAlertFields = (alert) => {
        if (!alert || typeof alert !== 'object') {
            return []
        }
        
        // Get the source data if it exists, otherwise use the alert directly
        const alertData = alert._source || alert
        
        const sortedEntries = sortAlertFields(alertData)
        
        return sortedEntries
            .filter(([key, value]) => shouldDisplayField(key, value))
            .map(([key, value]) => ({
                key,
                value,
                formattedValue: formatFieldValue(value),
                isImportant: isImportantField(key)
            }))
    }

    const getAlertSummary = (alert) => {
        const alertData = alert._source || alert
        
        return {
            id: alert._id || 'Unknown ID',
            timestamp: alertData['@timestamp'] || alertData.timestamp,
            severity: alertData.severity || 'unknown',
            ruleName: alertData.rule_name || alertData.title || 'Unknown Rule',
            description: alertData.description || alertData.message || 'No description available'
        }
    }

    const formatDate = (dateString) => {
        return formatDateTime(dateString)
    }

    return {
        formatDateTime,
        formatDate,
        formatSeverity,
        getSeverityClass,
        formatRuleName,
        formatFieldValue,
        isImportantField,
        shouldDisplayField,
        sortAlertFields,
        getDisplayableAlertFields,
        getAlertSummary
    }
}