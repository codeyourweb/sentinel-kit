#!/bin/bash

EVTX_DIR="/fluent-bit/logs/evtx"
PROCESSED_DIR="/fluent-bit/logs/evtx/processed"

mkdir -p "$PROCESSED_DIR"

find "$EVTX_DIR" -maxdepth 1 -name "*.evtx" -print0 | while IFS= read -r -d $'\0' evtx_file; do
    filename=$(basename "$evtx_file" .evtx)
    jsonl_file="${EVTX_DIR}/${filename}.jsonl"
    echo "Converting $evtx_file to $jsonl_file"
    
    /usr/bin/evtx_dump -f "$jsonl_file" -o jsonl "$evtx_file"
    
    if [ $? -eq 0 ]; then
        echo "Conversion successful. Moving original..."
        mv "$evtx_file" "$PROCESSED_DIR/"
    else
        echo "Error occurred during evtx_dump for $evtx_file." >&2
    fi
done