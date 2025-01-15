<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Factory\ProductFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::new()->create([
            'email' => 'lemon@example.com',
            'plainPassword' => 'lemonpass',
            'firstName' => 'Lemon',
        ]);

        ProductFactory::new()->create([
            'name' => 'Classic Lemonade',
            'price' => 99,
            'slug' => 'classic-lemonade',
            'lsVariantId' => '579933',
        ]);
        ProductFactory::new()->create([
            'name' => 'Watermelon Lemonade',
            'price' => 199,
            'slug' => 'watermelon-lemonade',
            'lsVariantId' => '586559',
        ]);
        ProductFactory::new()->create([
            'name' => 'Apple Lemonade',
            'price' => 299,
            'slug' => 'apple-lemonade',
        ]);
        ProductFactory::new()->create([
            'name' => 'Strawberry Lemonade',
            'price' => 399,
            'slug' => 'strawberry-lemonade',
        ]);
        ProductFactory::new()->create([
            'name' => 'Orange Lemonade',
            'price' => 399,
            'slug' => 'orange-lemonade',
        ]);
        ProductFactory::new()->create([
            'name' => 'Cherry Lemonade',
            'price' => 399,
            'slug' => 'cherry-lemonade',
        ]);

        $manager->flush();
    }
}
