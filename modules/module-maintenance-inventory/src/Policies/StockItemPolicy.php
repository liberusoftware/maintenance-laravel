<?php

declare(strict_types=1);

namespace Liberu\Modules\Maintenance\Inventory\Policies;

use Liberu\Modules\Maintenance\Inventory\Models\StockItem;

class StockItemPolicy
{
    public function viewAny(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function view(object $user, StockItem $item): bool
    {
        return (int) $user->currentTeam?->id === (int) $item->team_id;
    }

    public function create(object $user): bool
    {
        return $user->currentTeam !== null;
    }

    public function update(object $user, StockItem $item): bool
    {
        return $this->view($user, $item);
    }

    public function delete(object $user, StockItem $item): bool
    {
        return $this->view($user, $item);
    }
}
