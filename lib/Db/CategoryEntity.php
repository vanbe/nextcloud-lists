<?php

declare(strict_types=1);

namespace OCA\Lists\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getListId()
 * @method void setListId(int $listId)
 * @method string getName()
 * @method void setName(string $name)
 * @method int getPosition()
 * @method void setPosition(int $position)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class CategoryEntity extends Entity {
    protected int $listId = 0;
    protected string $name = '';
    protected int $position = 0;
    protected int $createdAt = 0;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('listId', 'integer');
        $this->addType('position', 'integer');
        $this->addType('createdAt', 'integer');
    }

    public function jsonSerialize(): array {
        return [
            'id'        => $this->getId(),
            'listId'    => $this->getListId(),
            'name'      => $this->getName(),
            'position'  => $this->getPosition(),
            'createdAt' => $this->getCreatedAt(),
        ];
    }
}
