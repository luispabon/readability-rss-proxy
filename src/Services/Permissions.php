<?php
declare(strict_types=1);

namespace App\Services;

use App\Entity\RssUser;

/**
 * Miscellaneous tools around checking permissions.
 */
class Permissions
{
    /**
     * Our security model around who can edit content is very simple: either admin, or the content owner.
     *
     * Given the content owner and the user trying to do something, reach a decision.
     */
    public function canEditContentFromUser(RssUser $contentOwner, RssUser $actingUser): bool
    {
        if (in_array('ROLE_ADMIN', $actingUser->getRoles(), true) === true) {
            return true;
        }

        if ($actingUser->getId() === $contentOwner->getId()) {
            return true;
        }

        return false;
    }
}
