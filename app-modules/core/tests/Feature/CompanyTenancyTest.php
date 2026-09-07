<?php

namespace Modules\Core\Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Core\Database\Seeders\RolesSeeder;
use Modules\Core\Enums\UserRole;
use Modules\Core\Models\Company;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CompanyTenancyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_creates_a_company(): void
    {
        /* Arrange & Act */
        $company = Company::factory()->create(['name' => 'Acme Inc']);

        /* Assert */
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Acme Inc']);
    }

    #[Test]
    public function it_attaches_users_to_a_company(): void
    {
        /* Arrange */
        $company = Company::factory()->create();
        $user = User::factory()->create();

        /* Act */
        $user->companies()->attach($company->id, ['is_owner' => true]);

        /* Assert */
        $this->assertTrue($user->fresh()->companies->contains($company));
        $this->assertTrue($company->fresh()->users->contains($user));
    }

    #[Test]
    public function customer_admin_can_access_the_company_panel(): void
    {
        /* Arrange */
        (new RolesSeeder())->run();
        $user = User::factory()->create();
        $user->assignRole(UserRole::CUSTOMER_ADMIN->value);

        /* Act & Assert */
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('company')));
    }

    #[Test]
    public function user_without_a_role_cannot_access_the_company_panel(): void
    {
        /* Arrange */
        (new RolesSeeder())->run();
        $user = User::factory()->create();

        /* Act & Assert */
        $this->assertFalse($user->canAccessPanel(Filament::getPanel('company')));
    }

    #[Test]
    public function super_admin_can_access_any_panel(): void
    {
        /* Arrange */
        (new RolesSeeder())->run();
        $user = User::factory()->create();
        $user->assignRole(UserRole::SUPER_ADMIN->value);

        /* Act & Assert */
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('company')));
        $this->assertTrue($user->isSuperAdmin());
    }

    #[Test]
    public function user_can_only_access_tenants_they_belong_to(): void
    {
        /* Arrange */
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create();
        $user->companies()->attach($ownCompany->id);

        /* Act & Assert */
        $this->assertTrue($user->canAccessTenant($ownCompany));
        $this->assertFalse($user->canAccessTenant($otherCompany));
    }

    #[Test]
    public function super_admin_can_access_any_tenant_without_belonging_to_it(): void
    {
        /* Arrange */
        (new RolesSeeder())->run();
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $user->assignRole(UserRole::SUPER_ADMIN->value);

        /* Act & Assert */
        $this->assertTrue($user->canAccessTenant($company));
    }

    #[Test]
    public function roles_seeder_is_idempotent(): void
    {
        /* Act */
        (new RolesSeeder())->run();
        (new RolesSeeder())->run();

        /* Assert */
        $this->assertDatabaseCount('roles', count(UserRole::cases()));
    }
}
