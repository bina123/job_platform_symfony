<?php

namespace App\Security\Voter;

use App\Entity\Application;
use App\Entity\Job;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ApplicationVoter extends Voter
{
    public const CREATE = 'APPLICATION_CREATE';
    public const VIEW = 'APPLICATION_VIEW';
    public const MANAGE = 'APPLICATION_MANAGE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE) {
            return $subject instanceof Job;
        }

        return in_array($attribute, [self::VIEW, self::MANAGE])
            && $subject instanceof Application;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return $user->hasRole('ROLE_DEVELOPER');
        }

        /** @var Application $application */
        $application = $subject;

        return match ($attribute) {
            self::VIEW => $application->getDeveloper()->getId() === $user->getId()
                || $application->getJob()->getEmployer()->getId() === $user->getId(),
            self::MANAGE => $application->getJob()->getEmployer()->getId() === $user->getId(),
            default => false,
        };
    }
}
