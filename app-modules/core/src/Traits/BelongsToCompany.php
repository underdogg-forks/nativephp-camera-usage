<?php

namespace Modules\Core\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\Company;

/**
 * Scopes a model to the currently authenticated user's company and injects
 * company_id on creation. Mirrors InvoicePlane-v2's Modules\Core\Traits\BelongsToCompany.
 */
trait BelongsToCompany
{
    public function scopeForCompany(Builder $query, $companyId = null): Builder
    {
        $companyId = $companyId ?: static::getCurrentCompanyId();

        return $query->where($query->getModel()->getTable().'.company_id', $companyId);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    protected static function getCurrentCompanyId(): ?int
    {
        if (function_exists('filament') && $tenant = filament()->getTenant()) {
            return $tenant->id;
        }

        if (session()?->has('current_company_id')) {
            return session('current_company_id');
        }

        $user = Auth::user();
        if (! $user) {
            return null;
        }

        return $user->companies()->first()?->id;
    }

    protected static function bootBelongsToCompany(): void
    {
        static::creating(function ($model): void {
            if (isset($model->company_id) && empty($model->company_id)) {
                $model->company_id = static::getCurrentCompanyId();
            }
        });

        static::addGlobalScope('company_id', function (Builder $builder): void {
            $companyId = static::getCurrentCompanyId();

            if (null !== $companyId) {
                $builder->where($builder->getModel()->getTable().'.company_id', $companyId);
            } elseif (Auth::check()) {
                $builder->whereRaw('1 = 0');
            }
        });
    }
}
