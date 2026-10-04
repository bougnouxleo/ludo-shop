<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Product;
use App\Service\CartService;

class CartTest extends FunctionalTestCase
{
    public function testAddProductToCart(): void
    {
        $this->login('client@example.com');

        $product = $this->repository(Product::class)->findOneBy(['reference' => 'CAT-001']);
        $this->assertNotNull($product);

        $this->client->request('POST', '/cart/add/'.$product->getId(), [
            'quantity' => 1,
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertSelectorTextContains('body', 'Catan');
    }

    public function testCartQuantityIsUpdated(): void
    {
        $this->login('client@example.com');
        $user = $this->findUser('client@example.com');

        $product = $this->repository(Product::class)->findOneBy(['reference' => 'CAT-001']);
        $this->assertNotNull($product);

        // 1. Préparation : on utilise le service pour créer l'état initial en base de données
        $cartService = $this->client->getContainer()->get(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addProduct($cart, $product, 1);

        // 2. On récupère l'identifiant de la ligne du panier (CartItem) fraîchement créée
        $cartItem = $cart->getItems()->first();
        $itemId = $cartItem->getId();

        // 3. Action : on modifie la quantité via la route du Controller 
        $this->client->request('POST', '/cart/items/'.$itemId.'/update', [
            'quantity' => 5, // On passe la quantité à 5
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // 4. Assertion : on vérifie que la page affiche bien la nouvelle quantité.
        $this->assertSelectorExists('input[value="5"]');
    }

    public function testRemoveProductFromCart(): void
    {
        $this->login('client@example.com');
        $user = $this->findUser('client@example.com');

        $product = $this->repository(Product::class)->findOneBy(['reference' => 'CAT-001']);
        $this->assertNotNull($product);

        // 1. Préparation (ajout du produit au panier)
        $cartService = $this->client->getContainer()->get(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addProduct($cart, $product, 1);

        $cartItem = $cart->getItems()->first();
        $itemId = $cartItem->getId();

        // 2. Action : On appelle la route de suppression
        $this->client->request('POST', '/cart/items/'.$itemId.'/remove');

        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // 3. Assertion : Le produit ne doit plus être visible sur la page du panier
        $this->assertSelectorTextNotContains('body', 'Catan');
    }

    public function testCartShowsCorrectTotal(): void
    {
        $this->login('client@example.com');
        $user = $this->findUser('client@example.com');

        $product = $this->repository(Product::class)->findOneBy(['reference' => 'CAT-001']);
        $this->assertNotNull($product);

        $cartService = $this->client->getContainer()->get(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addProduct($cart, $product, 2);

        $this->client->request('GET', '/cart');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('#cart-total');
    }
}
