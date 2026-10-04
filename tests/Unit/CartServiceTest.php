<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Service\PromotionService;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Product;
use App\Entity\User;
use App\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class CartServiceTest extends TestCase
{
    private CartService $service;

    protected function setUp(): void
    {
        // Création du stub pour simuler l'EntityManager sans se connecter à la BDD
        $em = $this->createStub(EntityManagerInterface::class);
        $promotionService = new PromotionService(); 
        $this->service = new CartService($em, $promotionService); 
    }

    public function testEmptyCartReturnsZero(): void
    {
        $user = $this->createStub(User::class);
        $cart = new Cart($user);

        $this->assertSame(0.0, $this->service->getTotal($cart));
    }

    public function testSingleItemReturnsCorrectTotal(): void
    {
        $user = $this->createStub(User::class);
        $cart = new Cart($user);

        $product = new Product();
        $product->setName('Catan');
        $product->setPrice(25.00);

        $item = new CartItem($product);
        $item->setQuantity(1);
        $item->setUnitPrice(25.00);
        $cart->addItem($item);

        $this->assertSame(25.00, $this->service->getTotal($cart));
    }

    public function testMultipleItems(): void
    {
        $user = $this->createStub(User::class);
        $cart = new Cart($user);

        // Premier produit
        $product1 = new Product();
        $product1->setName('Jeu 1');
        $product1->setPrice(10.00);
        $item1 = new CartItem($product1);
        $item1->setQuantity(1);
        $item1->setUnitPrice(10.00);
        $cart->addItem($item1);

        // Deuxième produit différent
        $product2 = new Product();
        $product2->setName('Jeu 2');
        $product2->setPrice(15.00);
        $item2 = new CartItem($product2);
        $item2->setQuantity(1);
        $item2->setUnitPrice(15.00);
        $cart->addItem($item2);

        // Le total doit être la somme des deux (10 + 15)
        $this->assertSame(25.00, $this->service->getTotal($cart));
    }

    public function testQuantityMultiplier(): void
    {
        $user = $this->createStub(User::class);
        $cart = new Cart($user);

        // Un seul produit mais avec une quantité de 3
        $product = new Product();
        $product->setName('Jeu en triple');
        $product->setPrice(20.00);

        $item = new CartItem($product);
        $item->setQuantity(3);
        $item->setUnitPrice(20.00);
        $cart->addItem($item);

        // Le total doit être le prix multiplié par la quantité (20 * 3)
        $this->assertSame(60.00, $this->service->getTotal($cart));
    }
    public function testPromotionalPriceIsUsed(): void
    {
        // 1. Préparation 
        $user = $this->createStub(User::class);
        $cart = new Cart($user);

        // Création du produit avec les dates 
        $product = new Product();
        $product->setName('Produit en promo');
        $product->setPrice(50.00);
        $product->setPromoPrice(35.00);
        $product->setPromoStartsAt(new \DateTimeImmutable('2026-01-01 00:00:00'));
        $product->setPromoEndsAt(new \DateTimeImmutable('2099-01-01 00:00:00'));
        $product->setStock(10);


        // 2. Action (Act)
        // On ajoute 2 exemplaires au panier via le service
        $this->service->addProduct($cart, $product, 2);

        // 3. Vérification (Assert)
        $this->assertSame(70.00, $this->service->getTotal($cart));
    }
}
