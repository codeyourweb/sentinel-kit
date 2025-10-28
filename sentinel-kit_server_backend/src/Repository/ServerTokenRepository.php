<?php

namespace App\Repository;

use App\Model\ServerToken;

class ServerTokenRepository
{
    public function isServerTokenValid(?string $token): bool
    {
        return $token === $_ENV['DATAMONITOR_SERVER_TOKEN'];
    }
}