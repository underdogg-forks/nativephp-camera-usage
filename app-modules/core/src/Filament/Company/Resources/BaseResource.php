<?php

namespace Modules\Core\Filament\Company\Resources;

use Filament\Resources\Resource;

/**
 * Shared base for every Filament resource registered on the Company panel.
 * Tenant isolation itself is handled by the BelongsToCompany model trait
 * (proven in ExpenseCompanyScopingTest) — this just tells Filament which
 * relationship owns a record, so it can auto-fill company_id on create and
 * knows resources here are tenant-scoped rather than global.
 */
abstract class BaseResource extends Resource
{
    protected static ?string $tenantOwnershipRelationshipName = 'company';
}
