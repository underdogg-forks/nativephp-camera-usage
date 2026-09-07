<?php

namespace Modules\Core\Tests;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Modules\Core\Database\Seeders\RolesSeeder;
use Modules\Core\Enums\UserRole;
use Modules\Core\Models\Company;
use Tests\TestCase;

abstract class AbstractCompanyPanelTestCase extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        Artisan::call('migrate', ['--path' => 'app-modules/core/database/migrations', '--realpath' => false]);

        (new RolesSeeder())->run();

        $this->company = Company::factory()->create([
            'search_code' => 'TESTCO',
            'name' => 'Test Company',
            'slug' => 'test-company',
        ]);

        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company->id, ['is_owner' => true]);
        $this->user->assignRole(UserRole::CUSTOMER_ADMIN->value);

        Filament::setCurrentPanel(Filament::getPanel('company'));
        Filament::setTenant($this->company, isQuiet: true);

        session(['current_company_id' => $this->company->id]);
    }

    /**
     * Switch the active tenant mid-test, e.g. to create fixtures for a second company.
     */
    protected function switchTenant(Company $company): void
    {
        Filament::setTenant($company, isQuiet: true);
        session(['current_company_id' => $company->id]);
    }
}
