<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase">
            <tr>
                <th class="px-4 py-2">Student</th>
                <th class="px-4 py-2">Status</th>
                <th class="px-4 py-2">Score</th>
                <th class="px-4 py-2">Percentage</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse ($quiz->attempts as $attempt)
                <tr>
                    <td class="px-4 py-2">{{ $attempt->user->name }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-0.5 text-xs rounded-full {{ $attempt->status === 'auto_submitted' ? 'bg-orange-100 text-orange-700' : 'bg-green-100 text-green-700' }}">
                            {{ $attempt->status === 'auto_submitted' ? 'Auto-submitted' : 'Submitted' }}
                        </span>
                    </td>
                    <td class="px-4 py-2">{{ $attempt->score }} / {{ $totalMarks }}</td>
                    <td class="px-4 py-2">{{ $totalMarks > 0 ? round($attempt->score / $totalMarks * 100) : 0 }}%</td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-4 py-4 text-center text-gray-400">No attempts yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
