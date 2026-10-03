<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Product;
use App\Service\PromotionService;
use PHPUnit\Framework\TestCase;

class PromotionServiceTest extends TestCase
{
    private PromotionService $service;

    protected function setUp(): void
    {
        $this->service = new PromotionService();
    }

    public function testReturnsNormalPriceWhenNoPromotion(): void
    {
        $product = $this->createProduct(50.00);

        $this->assertSame(50.00, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testReturnsPromoPriceDuringPeriod(): void
    {
        $product = $this->createProduct(50.00, 30.00);
        $product->setPromoStartsAt(new \DateTimeImmutable('-10 days'));
        $product->setPromoEndsAt(new \DateTimeImmutable('+10 days'));

        $this->assertSame(30.00, $this->service->getCurrentPrice($product));
        $this->assertTrue($this->service->isOnPromotion($product));
    }

    public function testReturnsNormalPriceBeforePromotionPeriod(): void
    {
        $product = $this->createProduct(50.00, 30.00);
        $product->setPromoStartsAt(new \DateTimeImmutable('+5 days'));
        $product->setPromoEndsAt(new \DateTimeImmutable('+10 days'));

        $this->assertSame(50.00, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testReturnsNormalPriceAfterPromotionPeriod(): void
    {
        $product = $this->createProduct(50.00, 30.00);
        $product->setPromoStartsAt(new \DateTimeImmutable('-10 days'));
        $product->setPromoEndsAt(new \DateTimeImmutable('-5 days'));

        $this->assertSame(50.00, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testPromoPriceEqualToNormalIsNotActive(): void
    {
        $product = $this->createProduct(50.00, 50.00);
        $product->setPromoStartsAt(new \DateTimeImmutable('-10 days'));
        $product->setPromoEndsAt(new \DateTimeImmutable('+10 days'));

        $this->assertSame(50.00, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testPromoPriceGreaterThanNormalIsNotActive(): void
    {
        $product = $this->createProduct(50.00, 60.00); // Promo plus chère que le prix normal !
        $product->setPromoStartsAt(new \DateTimeImmutable('-10 days'));
        $product->setPromoEndsAt(new \DateTimeImmutable('+10 days'));

        $this->assertSame(50.00, $this->service->getCurrentPrice($product));
        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testInvertedDatesAreNotActive(): void
    {
        $product = $this->createProduct(50.00, 30.00);
        // Date de début APRES la date de fin
        $product->setPromoStartsAt(new \DateTimeImmutable('+10 days'));
        $product->setPromoEndsAt(new \DateTimeImmutable('-10 days'));

        $this->assertFalse($this->service->isOnPromotion($product));
    }

    public function testBoundaryStartIsIncluded(): void
    {
        $product = $this->createProduct(50.00, 30.00);
        $product->setPromoStartsAt(new \DateTimeImmutable('now')); // Démarre pile maintenant
        $product->setPromoEndsAt(new \DateTimeImmutable('+10 days'));

        $this->assertTrue($this->service->isOnPromotion($product));
    }

    public function testBoundaryEndIsIncluded(): void
    {
        $product = $this->createProduct(50.00, 30.00);
        $product->setPromoStartsAt(new \DateTimeImmutable('-10 days'));
        // Finit pile maintenant (+1 seconde pour éviter que le test échoue si PHP met 1 milliseconde de trop à s'exécuter)
        $product->setPromoEndsAt(new \DateTimeImmutable('+1 second'));

        $this->assertTrue($this->service->isOnPromotion($product));
    }

    private function createProduct(float $price, ?float $promoPrice = null): Product
    {
        $product = new Product();
        $product->setName('Test Product')->setPrice($price);

        if (null !== $promoPrice) {
            $product->setPromoPrice($promoPrice);
            // Ces dates par défaut seront écrasées dans nos tests pour les rendre dynamiques
            $product->setPromoStartsAt(new \DateTimeImmutable('2026-08-01 00:00:00'));
            $product->setPromoEndsAt(new \DateTimeImmutable('2026-08-31 23:59:59'));
        }

        return $product;
    }
}
