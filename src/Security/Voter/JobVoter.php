<?php

namespace App\Security\Voter;

use App\Entity\Job;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class JobVoter extends Voter
{
    public const CREATE = 'JOB_CREATE';
    public const VIEW = 'JOB_VIEW';
    public const EDIT = 'JOB_EDIT';
    public const DELETE = 'JOB_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute === self::CREATE) {
            return true;
        }

        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof Job;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        if ($attribute === self::VIEW) {
            return true;
        }

        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if ($attribute === self::CREATE) {
            return $user->hasRole('ROLE_EMPLOYER'); // only employers can create jobs
        }

        /** @var Job $job */
        $job = $subject;

        // For EDIT and DELETE, only the employer who created the job can edit/delete it
        return $job->getEmployer()->getId() === $user->getId();
    }
}
