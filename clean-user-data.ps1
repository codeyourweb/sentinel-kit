Remove-Item ./data/caddy_logs/* -Recurse -Force
Remove-Item ./data/ftp_data/* -Recurse -Force
Remove-Item ./data/grafana/* -Recurse -Force
Remove-Item ./data/kibana/* -Recurse -Force
Remove-Item ./data/mysql_data/* -Recurse -Force
docker compose down -v