#!/bin/sh
if [ x$ELASTICSEARCH_PASSWORD == x ]; then
    echo "Set the ELASTICSEARCH_PASSWORD environment variable in the .env file";
    exit 1;
fi;

if [ ! -f config/certs/ca/ca.crt ]; then
    echo "Creating CA";
    bin/elasticsearch-certutil ca --silent --pem -out config/certs/ca.zip;
    unzip -o config/certs/ca.zip -d config/certs;
    rm -f config/certs/ca.zip;
fi;

if [ ! -f config/certs/certs.zip ]; then
echo "Creating certs";
cat <<EOF > config/certs/instances.yml
instances:
  - name: sentinel-kit-db-elasticsearch-es01
    dns:
      - sentinel-kit-db-elasticsearch-es01
      - localhost
    ip:
      - 127.0.0.1
EOF
            
# optional secondary node (Multi-node)
if [ "$ELASTICSEARCH_CLUSTER_MODE" == "multi-node" ]; then
echo "Cluster mode is multi-node, adding es02 cert entry.";
cat <<EOF >> config/certs/instances.yml
  - name: sentinel-kit-db-elasticsearch-es02
    dns:
      - sentinel-kit-db-elasticsearch-es02
      - localhost
    ip:
      - 127.0.0.1
EOF
else
    echo "Cluster mode is single-node, only es01 cert will be created.";
fi;

bin/elasticsearch-certutil cert --silent --pem -out config/certs/certs.zip --in config/certs/instances.yml --ca-cert config/certs/ca/ca.crt --ca-key config/certs/ca/ca.key;
unzip -o config/certs/certs.zip -d config/certs;
rm -f config/certs/certs.zip;
fi;

echo "Setting file permissions"
chown -R root:root config/certs;
find . -type d -exec chmod 750 \{\} \;;
find . -type f -exec chmod 640 \{\} \;;
echo "Waiting for Elasticsearch availability";
until curl -s --cacert config/certs/ca/ca.crt https://sentinel-kit-db-elasticsearch-es01:9200 | grep -q "missing authentication credentials"; do sleep 30; done;
echo "Setting kibana_system password";
until curl -s -X POST --cacert config/certs/ca/ca.crt -u "elastic:${ELASTICSEARCH_PASSWORD}" -H "Content-Type: application/json" https://sentinel-kit-db-elasticsearch-es01:9200/_security/user/kibana_system/_password -d "{\"password\":\"s3nt1n3lkit_k1b4n4_syst3m_p4sswd\"}" | grep -q "^{}"; do sleep 10; done;

echo "Creating sentinelkit-logs index template with higher priority";
curl -s -X PUT --cacert config/certs/ca/ca.crt -u "elastic:${ELASTICSEARCH_PASSWORD}" \
  -H "Content-Type: application/json" \
  https://sentinel-kit-db-elasticsearch-es01:9200/_index_template/sentinelkit-logs \
  -d '{
    "index_patterns": ["sentinelkit-*"],
    "priority": 300,
    "data_stream": {},
    "template": {
      "settings": {
        "index.lifecycle.name": "logs"
      }
    },
    "composed_of": ["logs@settings", "logs@mappings", "ecs@mappings"]
  }';

echo "Sentinelkit logs template created successfully";

echo "Waiting for Kibana availability";
until curl -s http://sentinel-kit-utils-kibana:5601/api/status | grep -q '"level":"available"'; do sleep 10; done;

echo "Creating Kibana data view for sentinelkit-* logs";
curl -s -X POST "http://sentinel-kit-utils-kibana:5601/api/data_views/data_view" \
  -H "Content-Type: application/json" \
  -H "kbn-xsrf: true" \
  -u "elastic:${ELASTICSEARCH_PASSWORD}" \
  -d '{
    "data_view": {
      "title": "sentinelkit-*",
      "name": "Sentinel-Kit Logs",
      "timeFieldName": "@timestamp"
    }
  }';

echo "Kibana data view created successfully";
echo "All done!"