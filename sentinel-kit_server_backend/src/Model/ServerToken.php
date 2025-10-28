<?php

namespace App\Model;
use App\Repository\ServerTokenRepository;

class ServerToken
{

    private ?string $serverToken = null;

    public function getServerToken(): ?string
    {
        return $this->serverToken;
    }

    public function setServerToken(string $token): static
    {
        $this->serverToken = $token;

        return $this;
    }
}