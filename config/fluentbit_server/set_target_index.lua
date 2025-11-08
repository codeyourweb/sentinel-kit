function set_target_index(tag, timestamp, record)
    local exit_code = 0

    if record["target_index"] == nil then
        record["target_index"] = "undefined-index"
        exit_code = 1
    end

    return exit_code, timestamp, record
end