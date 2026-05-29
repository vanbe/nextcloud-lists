<?php

declare(strict_types=1);

namespace OCA\Lists\Controller;

use OCA\Lists\Db\ShareEntity;
use OCA\Lists\Exception\ForbiddenException;
use OCA\Lists\Exception\NotFoundException;
use OCA\Lists\Service\ShareService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class ShareController extends OCSController {
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly ShareService $service,
        private readonly string $userId,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    public function index(int $listId): DataResponse {
        try {
            $shares = $this->service->findAll($listId, $this->userId);
            return new DataResponse(array_map(fn($s) => $s->jsonSerialize(), $shares));
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'List not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function create(int $listId, int $shareType, string $shareWith, int $permissions = ShareEntity::PERM_READ): DataResponse {
        if (!in_array($shareType, [ShareEntity::TYPE_USER, ShareEntity::TYPE_GROUP], true)) {
            return new DataResponse(['message' => 'Invalid shareType'], Http::STATUS_BAD_REQUEST);
        }
        if (trim($shareWith) === '') {
            return new DataResponse(['message' => 'shareWith is required'], Http::STATUS_BAD_REQUEST);
        }
        if (!in_array($permissions, [ShareEntity::PERM_READ, ShareEntity::PERM_WRITE], true)) {
            return new DataResponse(['message' => 'Invalid permissions'], Http::STATUS_BAD_REQUEST);
        }

        try {
            $entity = $this->service->create($listId, $this->userId, $shareType, $shareWith, $permissions);
            return new DataResponse($entity->jsonSerialize(), Http::STATUS_CREATED);
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'List not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function update(int $listId, int $id, int $permissions): DataResponse {
        if (!in_array($permissions, [ShareEntity::PERM_READ, ShareEntity::PERM_WRITE], true)) {
            return new DataResponse(['message' => 'Invalid permissions'], Http::STATUS_BAD_REQUEST);
        }
        try {
            $entity = $this->service->update($id, $listId, $this->userId, $permissions);
            return new DataResponse($entity->jsonSerialize());
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }

    #[NoAdminRequired]
    public function destroy(int $listId, int $id): DataResponse {
        try {
            $this->service->delete($id, $listId, $this->userId);
            return new DataResponse();
        } catch (NotFoundException) {
            return new DataResponse(['message' => 'Not found'], Http::STATUS_NOT_FOUND);
        } catch (ForbiddenException) {
            return new DataResponse(['message' => 'Forbidden'], Http::STATUS_FORBIDDEN);
        }
    }
}
