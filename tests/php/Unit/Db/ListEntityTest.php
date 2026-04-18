<?php

declare(strict_types=1);

namespace OCA\Lists\Tests\Unit\Db;

use OCA\Lists\Db\ListEntity;
use PHPUnit\Framework\TestCase;

class ListEntityTest extends TestCase {
    public function testGettersSetters(): void {
        $e = new ListEntity();
        $e->setUid('alice');
        $e->setName('My List');
        $e->setDescription('A description');
        $e->setIcon('cart');
        $e->setCreatedAt(1000);
        $e->setUpdatedAt(2000);

        $this->assertSame('alice', $e->getUid());
        $this->assertSame('My List', $e->getName());
        $this->assertSame('A description', $e->getDescription());
        $this->assertSame('cart', $e->getIcon());
        $this->assertSame(1000, $e->getCreatedAt());
        $this->assertSame(2000, $e->getUpdatedAt());
    }

    public function testNullableFieldsDefaultToNull(): void {
        $e = new ListEntity();
        $this->assertNull($e->getDescription());
        $this->assertNull($e->getIcon());
    }

    public function testNullableFieldsAcceptNull(): void {
        $e = new ListEntity();
        $e->setDescription(null);
        $e->setIcon(null);
        $this->assertNull($e->getDescription());
        $this->assertNull($e->getIcon());
    }
}
