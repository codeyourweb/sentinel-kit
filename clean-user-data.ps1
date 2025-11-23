Remove-Item ./config/backend/.initial_setup_done -Force
Remove-Item ./config/caddy_server/certificates/* -Recurse -Force
Remove-Item ./sentinel-kit_server_backend/config/jwt/*.pem -Force
Remove-Item ./sentinel-kit_server_backend/.initial_setup_done -Force
Remove-Item ./data/caddy_logs/* -Recurse -Force
Remove-Item ./data/ftp_data/* -Recurse -Force
Remove-Item ./data/grafana/* -Recurse -Force
Remove-Item ./data/kibana/* -Recurse -Force
Remove-Item ./data/log_ingest_data/auditd/* -Recurse -Force
Remove-Item ./data/log_ingest_data/evtx/* -Recurse -Force
Remove-Item ./data/log_ingest_data/json/* -Recurse -Force
Remove-Item ./data/fluentbit_db/* -Recurse -Force
Remove-Item ./data/yara_triage_data/* -Recurse -Force
docker compose down -v