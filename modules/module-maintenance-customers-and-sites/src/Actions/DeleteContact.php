<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\CustomersAndSites\Actions;

use Liberu\Modules\Maintenance\CustomersAndSites\Models\Contact;

final class DeleteContact
{
    public function handle(int $teamId, Contact $contact): void
    {
        abort_unless((int) $contact->team_id === $teamId, 404);
        $contact->delete();
    }
}
