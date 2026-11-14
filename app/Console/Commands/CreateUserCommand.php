<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Division;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateUserCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:create-user 
                            {--role= : Role pengguna (owner, admin_finance, manager, pemohon)} 
                            {--name= : Nama pengguna} 
                            {--email= : Alamat email pengguna}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat akun pengguna baru secara aman via terminal (misal: inisialisasi akun Owner atau Admin pertama)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('=== Inisialisasi Akun Pengguna JMM Disposisi ===');

        $name = $this->option('name') ?: $this->ask('Masukkan Nama Lengkap');
        $email = $this->option('email') ?: $this->ask('Masukkan Alamat Email');

        $availableRoles = Role::pluck('name')->toArray();
        $roleName = $this->option('role');

        if (!$roleName || !in_array($roleName, $availableRoles, true)) {
            $roleName = $this->choice('Pilih Peran Akun', $availableRoles, 0);
        }

        $role = Role::where('name', $roleName)->first();

        // Divisi opsional
        $divisionId = null;
        if ($roleName !== Role::ROLE_OWNER) {
            $divisions = Division::active()->pluck('name', 'id')->toArray();
            if (!empty($divisions)) {
                $divOptions = ['0' => 'Tanpa Divisi'] + $divisions;
                $chosenDiv = $this->choice('Pilih Divisi Pengguna', $divOptions, '0');
                if ($chosenDiv !== 'Tanpa Divisi') {
                    $divisionId = array_search($chosenDiv, $divisions, true);
                }
            }
        }

        // Password securely entered
        $password = $this->secret('Masukkan Password Baru (Minimal 8 Karakter)');
        $passwordConfirm = $this->secret('Konfirmasi Password Baru');

        if ($password !== $passwordConfirm) {
            $this->error('Konfirmasi password tidak cocok!');
            return self::FAILURE;
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.unique' => 'Email ini sudah terdaftar di sistem!',
            'password.min' => 'Password minimal harus 8 karakter.',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $err) {
                $this->error($err);
            }
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role_id' => $role->id,
            'division_id' => $divisionId,
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => null, // console
            'action' => 'cli_user_create',
            'auditable_type' => User::class,
            'auditable_id' => $user->id,
            'old_values' => null,
            'new_values' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $role->name,
            ],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Artisan CLI Command',
            'created_at' => now(),
        ]);

        $this->info("Berhasil! Akun {$user->name} ({$user->email}) dengan peran '{$role->display_name}' telah dibuat.");

        return self::SUCCESS;
    }
}

