function add_timestamp(tag, timestamp, record)
    
    local event_timestamp = nil
    
    if record["Event"] ~= nil and record["Event"]["System"] ~= nil and 
       record["Event"]["System"]["TimeCreated"] ~= nil and 
       record["Event"]["System"]["TimeCreated"]["#attributes"] ~= nil and
       record["Event"]["System"]["TimeCreated"]["#attributes"]["SystemTime"] ~= nil then
       
        event_timestamp = record["Event"]["System"]["TimeCreated"]["#attributes"]["SystemTime"]
    end

    
    if record["@timestamp"] == nil and record["timestamp"] ~= nil then
        event_timestamp = record["timestamp"]
        record["timestamp"] = nil
    end

    if event_timestamp ~= nil and event_timestamp ~= "" then
        record["@timestamp"] = event_timestamp
    else
        if record["@timestamp"] == nil then
            local seconds = math.floor(timestamp)
            local nanoseconds = (timestamp - seconds) * 1e9
            local milliseconds = math.floor(nanoseconds / 1e6)
            local formatted_time = os.date("!%Y-%m-%dT%H:%M:%S", seconds) .. string.format(".%03dZ", milliseconds)
            record["@timestamp"] = formatted_time
        end
    end

    return 1, timestamp, record
end