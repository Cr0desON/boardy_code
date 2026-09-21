<?php

namespace App\Listeners;

use Laravel\Passport\Events\AccessTokenCreated;

class AddAudienceToToken {
    public function handle(AccessTokenCreated $event) {
        // Passport уже создал токен, но мы можем влиять
        // через JWT-builder ниже
    }
}
