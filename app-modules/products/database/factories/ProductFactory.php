<?php

namespace Modules\Products\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Core\Models\Company;
use Modules\Products\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'unit_id' => null,
            'name' => $this->faker->words(3, true),
            'price' => $this->faker->randomFloat(4, 5, 500),
        ];
    }
}
