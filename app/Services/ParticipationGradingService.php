<?php

namespace App\Services;

use App\Models\Group;
use App\Models\ParticipationMark;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Computes participation marks per the criteria a lecturer configures:
 * points per topic started, per reply posted and per question answered,
 * capped at 100. Recomputing simply upserts the ParticipationMark row for
 * the given user/group/period so it can be re-run at any time.
 */
class ParticipationGradingService
{
    private const POINTS_PER_TOPIC = 4;

    private const POINTS_PER_POST = 2;

    private const POINTS_PER_ACCEPTED_ANSWER = 6;

    private const MAX_SCORE = 100;

    public function currentPeriod(): string
    {
        return now()->format('Y-m');
    }

    public function recomputeForGroup(Group $group, ?string $period = null): void
    {
        $period ??= $this->currentPeriod();
        [$start, $end] = $this->periodBounds($period);

        foreach ($group->members as $member) {
            $this->recomputeForUser($member, $group, $period, $start, $end);
        }
    }

    public function recomputeForUser(User $user, Group $group, ?string $period = null, ?Carbon $start = null, ?Carbon $end = null): ParticipationMark
    {
        $period ??= $this->currentPeriod();

        if (! $start || ! $end) {
            [$start, $end] = $this->periodBounds($period);
        }

        $topicCount = $group->topics()->where('created_by', $user->id)
            ->whereBetween('created_at', [$start, $end])->count();

        $postCount = \App\Models\Post::whereHas('topic', fn ($q) => $q->where('group_id', $group->id))
            ->where('author_id', $user->id)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $acceptedAnswers = \App\Models\Post::whereHas('topic', fn ($q) => $q->where('group_id', $group->id))
            ->where('author_id', $user->id)
            ->where('is_answer', true)
            ->whereBetween('created_at', [$start, $end])
            ->count();

        $score = min(
            self::MAX_SCORE,
            $topicCount * self::POINTS_PER_TOPIC
                + $postCount * self::POINTS_PER_POST
                + $acceptedAnswers * self::POINTS_PER_ACCEPTED_ANSWER
        );

        return ParticipationMark::updateOrCreate(
            ['user_id' => $user->id, 'group_id' => $group->id, 'period' => $period, 'criteria' => 'overall_participation'],
            ['score' => $score]
        );
    }

    public function gradeFor(int $score): string
    {
        return match (true) {
            $score >= 80 => 'A',
            $score >= 70 => 'B+',
            $score >= 60 => 'B',
            $score >= 50 => 'C+',
            $score >= 40 => 'C',
            default => 'D',
        };
    }

    private function periodBounds(string $period): array
    {
        $start = Carbon::createFromFormat('Y-m', $period)->startOfMonth();
        $end = $start->clone()->endOfMonth();

        return [$start, $end];
    }
}
