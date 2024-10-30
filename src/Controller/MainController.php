<?php

namespace App\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(EntityManagerInterface $entityManager): Response
    {
        $products = $entityManager->getRepository(Product::class)
            ->findAll();

        return $this->render('main/homepage.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/pricing', name: 'app_pricing')]
    public function pricing(): Response
    {
        return $this->render('main/pricing.html.twig');
    }

    #[Route('/product/{slug:product}', name: 'app_product_show')]
    public function showProduct(Product $product): Response
    {
        return $this->render('main/product-show.html.twig', [
            'product' => $product,
        ]);
    }
}
