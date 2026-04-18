<?php

declare(strict_types=1);

namespace OCA\Lists\Controller;

use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;

class UserController extends OCSController {
    public function __construct(
        string $appName,
        IRequest $request,
        private readonly IUserManager  $userManager,
        private readonly IGroupManager $groupManager,
    ) {
        parent::__construct($appName, $request);
    }

    #[NoAdminRequired]
    public function searchUsers(string $q = ''): DataResponse {
        if (mb_strlen($q) < 2) {
            return new DataResponse([]);
        }

        $users = $this->userManager->searchDisplayName($q, 5);
        $results = array_map(fn($u) => [
            'uid'         => $u->getUID(),
            'displayName' => $u->getDisplayName(),
        ], $users);

        return new DataResponse(array_values($results));
    }

    #[NoAdminRequired]
    public function searchGroups(string $q = ''): DataResponse {
        if (mb_strlen($q) < 2) {
            return new DataResponse([]);
        }

        $groups = $this->groupManager->search($q, 5);
        $results = array_map(fn($g) => [
            'gid'         => $g->getGID(),
            'displayName' => $g->getDisplayName(),
        ], $groups);

        return new DataResponse(array_values($results));
    }
}
