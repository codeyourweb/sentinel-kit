rm -rf ./data/caddy_logs/*
rm -rf ./data/ftp_data/*
rm -rf ./data/grafana/*
rm -rf ./data/kibana/*
rm -rf ./data/log_ingest_data/evtx/*
rm -rf ./data/log_ingest_data/auditd/*
rm -rf ./data/log_ingest_data/json/*
rm -rf ./data/fluentbit_db/*
rm -rf ./data/yara_triage_data/*
docker compose down -v