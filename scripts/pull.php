<?php

namespace go1\monolith\scripts;

passthru('docker-compose -f docker-compose.yml -f docker-compose-scorm.yml pull');

// Pull images that can't be pull by the command above.
passthru('docker pull go1com/php:7-nginx');
passthru('docker pull node:7-alpine');
passthru('docker pull registry.code.go1.com.au/microservices/work:master');
passthru('docker pull registry.code.go1.com.au/microservices/consumer:master');
