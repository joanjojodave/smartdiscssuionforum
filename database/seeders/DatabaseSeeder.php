<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\Membership;
use App\Models\Message;
use App\Models\ParticipationMark;
use App\Models\Post;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Topic;
use App\Models\User;
use App\Services\ParticipationGradingService;
use App\Services\QuizGradingService;
use App\Services\TopicClassifierService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'System Admin',
            'email' => 'admin@sdf.test',
            'role' => 'admin',
        ]);

        $lecturer = User::factory()->create([
            'name' => 'Dr Ephrance Namugenyi',
            'email' => 'lecturer@sdf.test',
            'role' => 'lecturer',
        ]);

        $studentNames = [
            'Oumo Steven', 'Kayengere Brenda', 'Namuwendo Judith Mukisa',
            'Nalubwama Joan Jojo', 'Bukenya Alpha Daniel', 'Grace Achieng', 'Peter Okello',
        ];

        $students = collect($studentNames)->map(function (string $name, int $i) {
            return User::factory()->create([
                'name' => $name,
                'email' => 'student'.($i + 1).'@sdf.test',
                'role' => 'member',
            ]);
        });

        $group1 = Group::create([
            'name' => 'Software Engineering 2026',
            'description' => 'Class discussion group for the Software Engineering recess assignment.',
            'rules' => "1. Be respectful to other members.\n2. Keep posts relevant to the course topics.\n3. No spamming or flooding the forum.\n4. Cite sources when sharing external material.",
            'created_by' => $admin->id,
            'inactivity_warning_days' => 7,
            'inactivity_blacklist_days' => 14,
            'blacklist_duration_days' => 7,
        ]);

        $group2 = Group::create([
            'name' => 'AI in Education - Research Team',
            'description' => 'Research team exploring machine learning applications in education.',
            'rules' => "1. Discussions must relate to the research theme.\n2. Share drafts and papers only with attribution.\n3. Respond to teammates within 48 hours where possible.",
            'created_by' => $admin->id,
            'inactivity_warning_days' => 10,
            'inactivity_blacklist_days' => 20,
            'blacklist_duration_days' => 10,
        ]);

        foreach ([$group1, $group2] as $group) {
            Membership::create([
                'user_id' => $admin->id, 'group_id' => $group->id, 'status' => 'active',
                'agreed_rules' => true, 'joined_at' => now()->subMonths(2), 'last_active_at' => now(),
            ]);
        }

        // Most students are active members of group 1.
        foreach ($students->take(5) as $i => $student) {
            Membership::create([
                'user_id' => $student->id, 'group_id' => $group1->id, 'status' => 'active',
                'agreed_rules' => true, 'joined_at' => now()->subWeeks(6), 'last_active_at' => now()->subDays($i),
            ]);
        }

        // One membership demonstrates the pending-onboarding state.
        Membership::create([
            'user_id' => $students[5]->id, 'group_id' => $group1->id, 'status' => 'pending', 'agreed_rules' => false,
        ]);

        // One membership demonstrates a warning, one demonstrates a blacklist.
        Membership::create([
            'user_id' => $students[6]->id, 'group_id' => $group1->id, 'status' => 'warned', 'warnings_count' => 1,
            'agreed_rules' => true, 'joined_at' => now()->subMonths(1), 'last_active_at' => now()->subDays(10),
        ]);

        foreach ($students->take(3) as $student) {
            Membership::create([
                'user_id' => $student->id, 'group_id' => $group2->id, 'status' => 'active',
                'agreed_rules' => true, 'joined_at' => now()->subWeeks(3), 'last_active_at' => now()->subDays(1),
            ]);
        }

        $classifier = app(TopicClassifierService::class);

        $this->seedTopic(
            $group1, $students[0], $classifier,
            'How does Laravel route model binding work?',
            'I keep seeing {group} in route definitions resolve straight to a Group instance in the controller. How does that actually work under the hood?',
            true,
            [
                [$students[1], 'Laravel inspects the type-hinted parameter and matches the route segment name to the variable name, then queries the model by its route key (usually the primary key).', true],
                [$students[2], 'You can also customize the resolution with getRouteKeyName() if you want to bind by slug instead of id.', false],
            ]
        );

        $this->seedTopic(
            $group1, $students[1], $classifier,
            'Database normalization - 2NF vs 3NF example needed',
            'Can someone walk through a concrete example showing the difference between second and third normal form?',
            true,
            [
                [$students[3], 'Still unanswered - could use a worked example with a students/courses table.', false],
            ]
        );

        $flooded = $this->seedTopic(
            $group1, $students[2], $classifier,
            'Check out my new phone!',
            'Just bought a new phone, so excited!!!',
            false,
            []
        );
        $flooded->posts()->first()?->update(['is_relevant' => false, 'relevance_score' => 0.05]);

        $this->seedTopic(
            $group2, $students[0], $classifier,
            'Which scikit-learn classifier works best for short text topic labels?',
            'For classifying short discussion titles into categories, would Naive Bayes or a linear SVM generalize better with limited training data?',
            true,
            [
                [$students[1], 'Naive Bayes tends to do well as a strong baseline on small, short-text datasets - worth starting there before an SVM.', true],
            ]
        );

        // Group chat sample with one excluded-recipient message (requirement #3).
        $msg1 = Message::create([
            'group_id' => $group1->id, 'sender_id' => $students[0]->id,
            'body' => 'Reminder: submit your quiz before Friday!', 'sync_status' => 'synced', 'sent_at' => now()->subHours(3),
        ]);
        Message::create([
            'group_id' => $group1->id, 'sender_id' => $lecturer->id,
            'body' => 'Good work on the last assignment everyone.', 'sync_status' => 'synced', 'sent_at' => now()->subHours(2),
        ]);
        $privateNote = Message::create([
            'group_id' => $group1->id, 'sender_id' => $students[1]->id,
            'body' => 'Can the two of you help me review my PR before the deadline?', 'sync_status' => 'synced', 'sent_at' => now()->subHour(),
        ]);
        $privateNote->exclusions()->create(['excluded_user_id' => $students[3]->id]);

        // Closed quiz with graded attempts, so the report view has data immediately.
        $closedQuiz = Quiz::create([
            'group_id' => $group1->id, 'lecturer_id' => $lecturer->id, 'title' => 'Week 3 Recap Quiz',
            'start_at' => now()->subDays(2), 'duration_minutes' => 20, 'target_category' => 'all students', 'status' => 'closed',
        ]);
        $q1 = $closedQuiz->questions()->create(['text' => 'Laravel is built on which language?', 'options' => ['A' => 'Python', 'B' => 'PHP', 'C' => 'Ruby', 'D' => 'Go'], 'correct_option' => 'B', 'marks' => 5]);
        $q2 = $closedQuiz->questions()->create(['text' => 'Which HTTP method is idempotent and safe?', 'options' => ['A' => 'POST', 'B' => 'PATCH', 'C' => 'GET', 'D' => 'DELETE'], 'correct_option' => 'C', 'marks' => 5]);

        $gradingService = app(QuizGradingService::class);
        foreach ($students->take(4) as $i => $student) {
            $attempt = QuizAttempt::create([
                'quiz_id' => $closedQuiz->id, 'user_id' => $student->id,
                'started_at' => now()->subDays(2)->addMinutes(2), 'submitted_at' => now()->subDays(2)->addMinutes(15),
                'status' => 'submitted',
            ]);
            $attempt->answers()->create(['question_id' => $q1->id, 'selected' => 'B']);
            $attempt->answers()->create(['question_id' => $q2->id, 'selected' => $i === 0 ? 'B' : 'C']);
            $gradingService->grade($attempt);
        }

        // Upcoming quiz - visible as an announcement, not yet open.
        $upcoming = Quiz::create([
            'group_id' => $group1->id, 'lecturer_id' => $lecturer->id, 'title' => 'Midterm Assessment',
            'start_at' => now()->addDays(3), 'duration_minutes' => 30, 'target_category' => 'all students', 'status' => 'scheduled',
        ]);
        $upcoming->questions()->create(['text' => 'What does MVC stand for?', 'options' => ['A' => 'Model View Controller', 'B' => 'Main View Component', 'C' => 'Model Variable Class', 'D' => 'Managed View Container'], 'correct_option' => 'A', 'marks' => 10]);

        $participationService = app(ParticipationGradingService::class);
        $participationService->recomputeForGroup($group1);
        $participationService->recomputeForGroup($group2);

        $this->command?->info('Seeded demo accounts (password: "password" for all):');
        $this->command?->line(' admin@sdf.test (admin) | lecturer@sdf.test (lecturer) | student1@sdf.test..student7@sdf.test (member)');
    }

    private function seedTopic(Group $group, User $author, TopicClassifierService $classifier, string $title, string $body, bool $isQuestion, array $replies): Topic
    {
        $classification = $classifier->classify($title.' '.$body);

        $topic = Topic::create([
            'group_id' => $group->id,
            'created_by' => $author->id,
            'title' => $title,
            'category' => $classification['category'],
            'ml_label' => $classification['category'],
        ]);

        $question = Post::create([
            'topic_id' => $topic->id,
            'author_id' => $author->id,
            'body' => $body,
            'is_question' => $isQuestion,
            'relevance_score' => 0.8,
        ]);

        foreach ($replies as [$replyAuthor, $replyBody, $isAnswer]) {
            $reply = Post::create([
                'topic_id' => $topic->id,
                'author_id' => $replyAuthor->id,
                'parent_post_id' => $question->id,
                'body' => $replyBody,
                'is_answer' => $isAnswer,
                'relevance_score' => 0.75,
            ]);

            if ($isAnswer) {
                $topic->update(['is_resolved' => true]);
            }
        }

        return $topic;
    }
}
