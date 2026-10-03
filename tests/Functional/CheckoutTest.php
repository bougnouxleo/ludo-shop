<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Order;
use App\Entity\Product;
use App\Service\CartService;

class CheckoutTest extends FunctionalTestCase
{
    public function testCheckoutPageRequiresCart(): void
    {
        $this->login('client@example.com');

        // On essaie d'accéder au checkout alors que le panier est vide
        $this->client->request('GET', '/checkout');

        // L'application doit nous rediriger (généralement vers /cart avec un message d'erreur)
        $this->assertResponseRedirects();
    }

    public function testCheckoutCreatesOrder(): void
    {
        $this->login('client@example.com');
        $user = $this->findUser('client@example.com');

        // 1. Ajouter un produit au panier
        $product = $this->repository(Product::class)->findOneBy(['reference' => 'CAT-001']);
        $this->assertNotNull($product);

        $cartService = $this->client->getContainer()->get(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addProduct($cart, $product, 1);

        // 2. Remplir le formulaire de checkout
        $crawler = $this->client->request('GET', '/checkout');
        $token = $crawler->filter('input[name="checkout_form[_token]"]')->attr('value');

        $this->client->request('POST', '/checkout', [
            'checkout_form' => [
                'addressLine' => '123 Rue Test',
                'postalCode' => '75001',
                'city' => 'Paris',
                'country' => 'FR',
                '_token' => $token,
            ],
        ]);

        // 3. Vérifier que l'order est créée
        $this->assertResponseRedirects();
        $order = $this->repository(Order::class)->findOneBy(['user' => $user]);
        $this->assertNotNull($order);
        $this->assertSame('pending', $order->getStatus()->value);
    }

    public function testPaymentCreatesPaidOrder(): void
    {
        $this->login('client@example.com');
        $user = $this->findUser('client@example.com');

        // Setup : on utilise notre méthode privée pour aller plus vite
        $order = $this->createPendingOrder($user);

        // 4. Payer la commande
        $this->client->request('POST', '/orders/'.$order->getId().'/pay');

        $this->assertResponseRedirects();

        // On vide le cache Doctrine pour forcer le rechargement depuis la base
        $this->entityManager()->clear();

        $paidOrder = $this->repository(Order::class)->find($order->getId());
        $this->assertSame('paid', $paidOrder->getStatus()->value);
    }

    public function testConfirmationPageIsDisplayed(): void
    {
        $this->login('client@example.com');
        $user = $this->findUser('client@example.com');

        // Setup : création d'une commande en attente
        $order = $this->createPendingOrder($user);

        // Action : on la paye
        $this->client->request('POST', '/orders/'.$order->getId().'/pay');

        // On suit la redirection post-paiement (vers la page de confirmation)
        $this->assertResponseRedirects();
        $this->client->followRedirect();

        // Assertion : on vérifie que la page s'affiche bien et contient l'ID de la commande
        $this->assertResponseIsSuccessful();

        // Note : Ajuste cette ligne selon ce qui s'affiche réellement sur ta page de confirmation
        // (ça peut être l'ID, une référence alphanumérique, ou juste un texte "Merci pour votre commande")
        $this->assertSelectorTextContains('body', (string) $order->getId());
    }

    /**
     * Méthode Helper privée pour factoriser la création d'une commande.
     */
    private function createPendingOrder($user): Order
    {
        $product = $this->repository(Product::class)->findOneBy(['reference' => 'CAT-001']);
        $cartService = $this->client->getContainer()->get(CartService::class);
        $cart = $cartService->getOrCreateCart($user);
        $cartService->addProduct($cart, $product, 1);

        $crawler = $this->client->request('GET', '/checkout');
        $token = $crawler->filter('input[name="checkout_form[_token]"]')->attr('value');

        $this->client->request('POST', '/checkout', [
            'checkout_form' => [
                'addressLine' => '123 Rue Test',
                'postalCode' => '75001',
                'city' => 'Paris',
                'country' => 'FR',
                '_token' => $token,
            ],
        ]);

        return $this->repository(Order::class)->findOneBy(['user' => $user]);
    }
}
