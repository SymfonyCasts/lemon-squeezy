<?php

namespace App\DataFixtures;

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
            'description' => 'A timeless, thirst-quenching beverage that blends the bright, zesty flavor of lemons with just the right amount of sweetness. Simple yet satisfying.',
            'slug' => 'classic-lemonade',
            'lsVariantId' => '579933',
        ]);
        ProductFactory::new()->create([
            'name' => 'Watermelon Lemonade',
            'price' => 199,
            'description' => 'A vibrant, thirst-quenching blend of sweet, juicy watermelon and zesty lemon. This refreshing drink captures the essence of summer in every sip',
            'slug' => 'watermelon-lemonade',
            'lsVariantId' => '586559',
        ]);
        ProductFactory::new()->create([
            'name' => 'Apple Lemonade',
            'price' => 299,
            'description' => 'A unique twist where the bright tartness of lemons meets the smooth, slightly sweet flavor of fresh apples.',
            'slug' => 'apple-lemonade',
        ]);
        ProductFactory::new()->create([
            'name' => 'Strawberry Lemonade',
            'price' => 399,
            'description' => 'A refreshing, sweet-tart drink combining the juicy, fruity flavor of ripe strawberries with the zesty, tangy kick of lemons.',
            'slug' => 'strawberry-lemonade',
        ]);
        ProductFactory::new()->create([
            'name' => 'Orange Lemonade',
            'price' => 399,
            'description' => 'A sunny, citrusy refreshment that blends the bright, tangy zing of lemons with the sweet, juicy flavor of ripe oranges',
            'slug' => 'orange-lemonade',
        ]);
        ProductFactory::new()->create([
            'name' => 'Cherry Lemonade',
            'price' => 399,
            'description' => 'A bright, fruity fusion of sweet cherries and tart lemons. It has a perfect balance of tangy and sweet, with a burst of cherry goodness that adds a bit of whimsy to every sip',
            'slug' => 'cherry-lemonade',
        ]);

        $manager->flush();
    }
}
