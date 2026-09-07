<?php

namespace Modules\Clients\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Clients\Enums\RelationType;
use Modules\Clients\Models\Relation;
use Modules\Core\Models\Company;

class RelationFactory extends Factory
{
    protected $model = Relation::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'relation_type' => $this->faker->randomElement([RelationType::CUSTOMER->value, RelationType::VENDOR->value]),
            'name' => $this->faker->company(),
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => ['relation_type' => RelationType::CUSTOMER->value]);
    }

    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => ['relation_type' => RelationType::VENDOR->value]);
    }
}
