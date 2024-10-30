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
        ]);
        ProductFactory::new()->create([
            'name' => 'Watermelon Lemonade',
            'price' => 199,
            'slug' => 'watermelon-lemonade',
        ]);
        ProductFactory::new()->create([
            'name' => 'Strawberry Lemonade',
            'price' => 299,
            'slug' => 'strawberry-lemonade',
        ]);
        ProductFactory::new()->create([
            'name' => 'Mango Lemonade',
            'price' => 399,
            'slug' => 'mango-lemonade',
        ]);

        $manager->flush();
    }
}
