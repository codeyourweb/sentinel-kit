function add_timestamp(tag, timestamp, record)
    if record["@timestamp"] == nil then
        local seconds = timestamp[1]
        local formatted_time = os.date("!%Y-%m-%dT%H:%M:%S", seconds) .. string.format(".%03dZ", timestamp[2]/1000000)
        record["@timestamp"] = formatted_time
    end

    return 1, timestamp, record
end