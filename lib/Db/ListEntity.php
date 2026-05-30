<?php

declare(strict_types=1);

namespace OCA\Lists\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method string getUid()
 * @method void setUid(string $uid)
 * @method string getName()
 * @method void setName(string $name)
 * @method string|null getDescription()
 * @method void setDescription(?string $description)
 * @method string|null getIcon()
 * @method void setIcon(?string $icon)
 * @method int getHasQuantities()
 * @method void setHasQuantities(int $hasQuantities)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 * @method int getUpdatedAt()
 * @method void setUpdatedAt(int $updatedAt)
 */
class ListEntity extends Entity {
    protected string $uid = '';
    protected string $name = '';
    protected ?string $description = null;
    protected ?string $icon = null;
    protected int $hasQuantities = -1; // sentinel: NC setter skips value unchanged from init
    protected int $createdAt = 0;
    protected int $updatedAt = 0;

    // Virtual fields — not persisted, computed on read
    private int $activeItemCount = 0;
    private ?int $userPosition = null;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('hasQuantities', 'integer');
        $this->addType('createdAt', 'integer');
        $this->addType('updatedAt', 'integer');
    }

    public function hasQuantities(): bool {
        return $this->hasQuantities === 1;
    }

    public function getActiveItemCount(): int {
        return $this->activeItemCount;
    }

    public function setActiveItemCount(int $count): void {
        $this->activeItemCount = $count;
    }

    public function getUserPosition(): ?int {
        return $this->userPosition;
    }

    public function setUserPosition(?int $position): void {
        $this->userPosition = $position;
    }

    public function jsonSerialize(): array {
        return [
            'id'              => $this->getId(),
            'uid'             => $this->getUid(),
            'name'            => $this->getName(),
            'description'     => $this->getDescription(),
            'icon'            => $this->getIcon(),
            'hasQuantities'   => $this->hasQuantities(),
            'position'        => $this->userPosition,
            'activeItemCount' => $this->activeItemCount,
            'createdAt'       => $this->getCreatedAt(),
            'updatedAt'       => $this->getUpdatedAt(),
        ];
    }
}
