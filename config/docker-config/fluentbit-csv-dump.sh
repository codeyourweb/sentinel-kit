#!/bin/bash

CSV_DIR="/fluent-bit/logs/csv"
JSON_DIR="/fluent-bit/logs/csv"
PROCESSED_DIR="/fluent-bit/logs/csv/processed"

mkdir -p "$PROCESSED_DIR" "$JSON_DIR"
find "$CSV_DIR" -maxdepth 1 -name "*.csv" -print0 | while IFS= read -r -d $'\0' csv_file; do
    filename=$(basename "$csv_file" .csv)
    jsonl_file="${JSON_DIR}/${filename}.jsonl"
    echo "Converting $csv_file to $jsonl_file..."
    
    if [ ! -s "$csv_file" ]; then
        echo "Warning: $csv_file is empty, skipping..."
        continue
    fi
    
    first_line=$(head -1 "$csv_file")
    if echo "$first_line" | grep -q ","; then
        sep=","
    elif echo "$first_line" | grep -q ";"; then
        sep=";"
    elif echo "$first_line" | grep -q $'\t'; then
        sep=$'\t'
    elif echo "$first_line" | grep -q "|"; then
        sep="|"
    else
        sep=","
    fi
    
    echo "Using separator: '$sep'"
    
    if echo "$first_line" | grep -q "[a-zA-Z]"; then
        echo "CSV file has headers, using column names"
        python3 -c "
import csv
import json
import sys

try:
    with open('$csv_file', 'r', encoding='utf-8') as f:
        sample = f.read(1024)
        f.seek(0)
        sniffer = csv.Sniffer()
        dialect = sniffer.sniff(sample, delimiters=',$sep')
        
        reader = csv.DictReader(f, dialect=dialect)
        with open('$jsonl_file', 'w', encoding='utf-8') as out_f:
            for row in reader:
                json.dump(row, out_f, ensure_ascii=False)
                out_f.write('\n')
    print('Conversion successful with Python CSV parser')
except Exception as e:
    print(f'Python conversion failed: {e}', file=sys.stderr)
    sys.exit(1)
"
    else
        echo "CSV file without headers, using column_0, column_1, etc."
        python3 -c "
import csv
import json
import sys

try:
    with open('$csv_file', 'r', encoding='utf-8') as f:
        sample = f.read(1024)
        f.seek(0)
        sniffer = csv.Sniffer()
        dialect = sniffer.sniff(sample, delimiters=',$sep')
        
        reader = csv.reader(f, dialect=dialect)
        with open('$jsonl_file', 'w', encoding='utf-8') as out_f:
            for row in reader:
                row_dict = {f'column_{i}': value for i, value in enumerate(row)}
                json.dump(row_dict, out_f, ensure_ascii=False)
                out_f.write('\n')
    print('Conversion successful with Python CSV parser (no headers)')
except Exception as e:
    print(f'Python conversion failed: {e}', file=sys.stderr)
    sys.exit(1)
"
    fi
    
    if [ $? -eq 0 ]; then
        echo "Conversion successful. Moving original..."
        mv "$csv_file" "$PROCESSED_DIR/"
    else
        echo "Error occurred during csv to json conversion for $csv_file." >&2
    fi
done
