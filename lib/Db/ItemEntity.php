<?php

declare(strict_types=1);

namespace OCA\Lists\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getListId()
 * @method void setListId(int $listId)
 * @method string getTitle()
 * @method void setTitle(string $title)
 * @method string|null getDescription()
 * @method void setDescription(?string $description)
 * @method int getChecked()
 * @method void setChecked(int $checked)
 * @method int|null getCheckedAt()
 * @method void setCheckedAt(?int $checkedAt)
 * @method int getPosition()
 * @method void setPosition(int $position)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
class ItemEntity extends Entity {
    protected int $listId = 0;
    protected string $title = '';
    protected ?string $description = null;
    protected int $checked = 0;
    protected ?int $checkedAt = null;
    protected int $position = 0;
    protected int $createdAt = 0;
    protected int $updatedAt = 0;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('listId', 'integer');
        $this->addType('checked', 'integer');
        $this->addType('checkedAt', 'integer');
        $this->addType('position', 'integer');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }

    public function isChecked(): bool {
        return $this->checked === 1;
    }

    public function jsonSerialize(): array {
        return [
            'id'          => $this->getId(),
            'listId'      => $this->getListId(),
            'title'       => $this->getTitle(),
            'description' => $this->getDescription(),
            'checked'     => $this->isChecked(),
            'checkedAt'   => $this->getCheckedAt(),
            'position'    => $this->getPosition(),
            'createdAt'   => $this->getCreatedAt(),
            'updatedAt'   => $this->getUpdatedAt(),
        ];
    }
}
