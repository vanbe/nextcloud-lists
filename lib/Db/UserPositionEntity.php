<?php

declare(strict_types=1);

namespace OCA\Lists\Db;

use OCP\AppFramework\Db\Entity;

/**
 * Per-user ordering of a list. `position = null` means the user hasn't
 * explicitly reordered this list; the read query falls back to created_at.
 *
 * @method string getUid()
 * @method void setUid(string $uid)
 * @method int getListId()
 * @method void setListId(int $listId)
 * @method int|null getPosition()
 * @method void setPosition(?int $position)
 */
class UserPositionEntity extends Entity {
    protected string $uid = '';
    protected int $listId = 0;
    // Sentinel: NC Entity::setter() skips values equal to init, so a real null position
    // wouldn't get persisted on update if init were null. Keep numeric sentinel; null
    // assignment goes through fine since we never re-update an existing row to null.
    protected ?int $position = -1;

    public function __construct() {
        $this->addType('id', 'integer');
        $this->addType('listId', 'integer');
        $this->addType('position', 'integer');
    }
}
