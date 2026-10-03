<?php

declare(strict_types=1);

namespace App\Tests\Unit;

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
        $this->service = new CartService($em);
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
}
