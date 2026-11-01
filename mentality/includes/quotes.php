<?php
// includes/quotes.php - 50 short quotes. One is shown per day (the same one all day, a different one tomorrow).

function all_quotes(): array {
    return [
        'Small steps every day add up to big changes.',
        'You don\'t have to be perfect, just consistent.',
        'Today\'s effort is tomorrow\'s strength.',
        'Progress, not perfection.',
        'Start where you are. Use what you have. Do what you can.',
        'Every habit is a vote for the person you want to become.',
        'Discipline today builds the freedom you want tomorrow.',
        'One good day at a time.',
        'Be patient with yourself. Growth takes time.',
        'Your future is shaped by what you do today.',
        'Showing up is half the work.',
        'Done is better than perfect.',
        'Rest if you must, but don\'t quit.',
        'Little by little, a little becomes a lot.',
        'The best time to start was yesterday. The next best time is now.',
        'Consistency beats intensity.',
        'Every expert was once a beginner.',
        'Your only competition is who you were yesterday.',
        'Don\'t let one bad day convince you to give up a good habit.',
        'Take care of your mind the way you take care of your body.',
        'It\'s okay to slow down. It\'s not okay to stop.',
        'Courage is taking the next step even when you can\'t see the whole path.',
        'Celebrate small wins; they fuel the big ones.',
        'Be kind to yourself on the days progress feels slow.',
        'Energy flows where focus goes.',
        'Make today count.',
        'A calm mind makes better choices.',
        'You are allowed to start over as many times as you need.',
        'Dreams need plans, and plans need action.',
        'What you do every day matters more than what you do once in a while.',
        'Choose progress over comfort.',
        'Your habits are quietly writing your story.',
        'Mistakes are proof that you are trying.',
        'Focus on the step in front of you.',
        'Good things take time, so keep going.',
        'Today is a fresh page. Write something you\'re proud of.',
        'Strong minds are built one day at a time.',
        'Don\'t wait for motivation. Build momentum.',
        'The goal is not to be better than others, but better than before.',
        'Breathe. You are doing better than you think.',
        'Every tick on your tracker is a promise kept to yourself.',
        'Hard days are part of the process, not the end of it.',
        'Build the life you want in small, steady pieces.',
        'Believe you can, then show up and prove it.',
        'Change begins with a single decision.',
        'Be proud of how far you\'ve come.',
        'Healthy habits are the best gift you can give your future self.',
        'Keep going. Your effort is not going unnoticed.',
        'Turn "I should" into "I will", then into "I did".',
        'Finish the day, forgive the day, and begin again tomorrow.',
    ];
}

// Picks the quote for today: random-looking, but the same for the whole day.
function daily_quote(): string {
    $quotes = all_quotes();
    $index  = hexdec(substr(md5(date('Y-m-d')), 0, 8)) % count($quotes);
    return $quotes[$index];
}
