<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Two-level visibility: operator staff (admin/user) see everything, a portal user
 * sees only their own customer's records.
 *
 * Scoping lives here rather than in a global model scope so the admin screens and
 * the portal can share one set of controllers without one of them having to
 * remember to unscope.
 */
trait ScopesToCustomer
{
    protected function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403, 'Admins only.');
    }

    /** The customer whose records the viewer is limited to, or null for staff. */
    protected function viewerCustomerId(): ?int
    {
        $user = auth()->user();

        if (! $user || $user->isAdmin()) {
            return null;
        }

        return $user->customer_id;
    }

    /**
     * Constrain a query to the viewer. $column lets callers point at a
     * denormalised customer_id or reach through a relation.
     */
    protected function scopeToViewer(Builder $query, string $column = 'customer_id'): Builder
    {
        $customerId = $this->viewerCustomerId();

        if ($customerId !== null) {
            $query->where($column, $customerId);
        }

        return $query;
    }

    /**
     * Same, for models that only reach a customer through their domain.
     */
    protected function scopeThroughDomain(Builder $query): Builder
    {
        $customerId = $this->viewerCustomerId();

        if ($customerId !== null) {
            $query->whereHas('domain', fn (Builder $q) => $q->where('customer_id', $customerId));
        }

        return $query;
    }

    /** 403 unless the record belongs to the viewer's customer. */
    protected function authorizeCustomer(?int $customerId): void
    {
        $viewer = $this->viewerCustomerId();

        if ($viewer !== null && $customerId !== $viewer) {
            abort(403);
        }
    }
}
