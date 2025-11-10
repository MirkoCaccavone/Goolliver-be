// Quick test per creare dati
\App\Models\User::firstOrCreate(
['email' => 'test@goolliver.com'],
[
'name' => 'Test User',
'username' => 'testuser',
'password' => bcrypt('password'),
'email_verified_at' => now()
]
);

\App\Models\Contest::firstOrCreate(
['title' => 'Test Photo Contest'],
[
'description' => 'Contest di test per il sistema foto',
'start_date' => now(),
'end_date' => now()->addDays(30),
'max_entries_per_user' => 5,
'is_public' => true,
'status' => 'active'
]
);

echo "Test data created successfully!";