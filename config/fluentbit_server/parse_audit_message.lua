function parse_auditd_message(tag, timestamp, record)
    local message = record["message"]
    
    if not message or message == "" then
        return 0, timestamp, record
    end

    local auditd_fields = {}

    for key, value in string.gmatch(message, "([%w_]+)=\"([^\"]*)\"") do
        auditd_fields[key] = value
    end
    
    for key, value in string.gmatch(message, "([%w_]+)=([%w%d%.%-%+]+)") do
        auditd_fields[key] = value
    end
    
    for k, v in pairs(record) do
        auditd_fields[k] = v
    end

    auditd_fields["auditd_raw_message"] = record["message"]
    
    return 1, timestamp, auditd_fields
end