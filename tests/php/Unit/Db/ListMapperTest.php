<?php

declare(strict_types=1);

namespace OCA\Lists\Tests\Unit\Db;

use OCA\Lists\Db\ListEntity;
use OCA\Lists\Db\ListMapper;
use OCA\Lists\Exception\NotFoundException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ListMapperTest extends TestCase {
    private IDBConnection&MockObject $db;
    private IQueryBuilder&MockObject $qb;
    private IExpressionBuilder&MockObject $expr;
    private ListMapper $mapper;

    protected function setUp(): void {
        $this->expr = $this->createMock(IExpressionBuilder::class);
        $this->expr->method('eq')->willReturn('1=1');

        $this->qb = $this->createMock(IQueryBuilder::class);
        $this->qb->method('select')->willReturnSelf();
        $this->qb->method('from')->willReturnSelf();
        $this->qb->method('where')->willReturnSelf();
        $this->qb->method('andWhere')->willReturnSelf();
        $this->qb->method('orderBy')->willReturnSelf();
        $this->qb->method('insert')->willReturnSelf();
        $this->qb->method('setValue')->willReturnSelf();
        $this->qb->method('executeStatement')->willReturn(1);
        $this->qb->method('expr')->willReturn($this->expr);
        $this->qb->method('createNamedParameter')->willReturnArgument(0);

        $this->db = $this->createMock(IDBConnection::class);
        $this->db->method('getQueryBuilder')->willReturn($this->qb);
        $this->db->method('lastInsertId')->willReturn(1);

        $this->mapper = new ListMapper($this->db);
    }

    public function testInsertSetsTimestamps(): void {
        $before = time();

        $entity = new ListEntity();
        $entity->setUid('alice');
        $entity->setName('Test');

        $this->mapper->insert($entity);

        $this->assertGreaterThanOrEqual($before, $entity->getCreatedAt());
        $this->assertGreaterThanOrEqual($before, $entity->getUpdatedAt());
    }

    public function testInsertDoesNotOverwriteExistingCreatedAt(): void {
        $entity = new ListEntity();
        $entity->setUid('alice');
        $entity->setName('Test');
        $entity->setCreatedAt(1000);

        $this->mapper->insert($entity);

        $this->assertSame(1000, $entity->getCreatedAt());
    }

    public function testFindThrowsNotFoundExceptionWhenMissing(): void {
        $mapper = $this->getMockBuilder(ListMapper::class)
            ->setConstructorArgs([$this->db])
            ->onlyMethods(['findEntity'])
            ->getMock();

        $mapper->method('findEntity')->willThrowException(new DoesNotExistException(''));

        $this->expectException(NotFoundException::class);
        $mapper->find(99, 'alice');
    }
}
