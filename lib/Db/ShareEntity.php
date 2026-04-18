<?php

declare(strict_types=1);

namespace OCA\Lists\Db;

use OCP\AppFramework\Db\Entity;

/**
 * @method int getListId()
 * @method void setListId(int $listId)
 * @method int getShareType()
 * @method void setShareType(int $shareType)
 * @method string getShareWith()
 * @method void setShareWith(string $shareWith)
 * @method int getPermissions()
 * @method void setPermissions(int $permissions)
 * @method int getCreatedAt()
 * @method void setCreatedAt(int $createdAt)
 */
class ShareEntity extends Entity {
    // share_type: 0 = user, 1 = group
    public const TYPE_USER  = 0;
    public const TYPE_GROUP = 1;

    // permissions bitmask: 1 = read, 3 = read+write
    public const PERM_READ  = 1;
    public const PERM_WRITE = 3;

    protected int $listId = 0;
    protected int $shareType = 0;
    protected string $shareWith = '';
    protected int $permissions = self::PERM_READ;
    protected int $createdAt = 0;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('listId', 'integer');
        $this->addType('shareType', 'integer');
        $this->addType('permissions', 'integer');
        $this->addType('createdAt', 'integer');
    }

    public function canWrite(): bool {
        return ($this->permissions & 2) === 2;
    }

    public function jsonSerialize(): array {
        return [
            'id'          => $this->getId(),
            'listId'      => $this->getListId(),
            'shareType'   => $this->getShareType(),
            'shareWith'   => $this->getShareWith(),
            'permissions' => $this->getPermissions(),
            'createdAt'   => $this->getCreatedAt(),
        ];
    }
}
