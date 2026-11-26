<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use ZipArchive;

class SuperAdminBackupTest extends TestCase
{
    use DatabaseMigrations;

    /** @var array<int, string> */
    private array $existingBackups = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->existingBackups = glob(storage_path('app/backups/sevs-backup-*.zip')) ?: [];
    }

    protected function tearDown(): void
    {
        foreach (glob(storage_path('app/backups/sevs-backup-*.zip')) ?: [] as $file) {
            if (! in_array($file, $this->existingBackups, true)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    private function superAdmin(): User
    {
        return User::create([
            'name' => 'Super Administrator',
            'username' => 'super-backup',
            'email' => 'super-backup@example.com',
            'password' => bcrypt('password'),
            'role' => 'SuperAdmin',
        ]);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Admin User',
            'username' => 'admin-backup',
            'email' => 'admin-backup@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
        ]);
    }

    public function test_super_admin_can_create_download_and_delete_a_backup(): void
    {
        $superAdmin = $this->superAdmin();
        $this->actingAs($superAdmin);

        $this->post(route('superadmin.backups.store'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $latest = app(BackupService::class)->latest();

        $this->assertNotNull($latest);
        $this->assertFileExists($latest['path']);

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($latest['path']) === true);
        $this->assertNotFalse($zip->locateName('database.sqlite'));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $zip->close();

        $this->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee($latest['name']);

        $this->get(route('superadmin.backups.download', $latest['name']))
            ->assertOk()
            ->assertDownload($latest['name']);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Created Database Backup',
            'user_name' => $superAdmin->username,
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'Downloaded Database Backup',
            'details' => "Downloaded backup '{$latest['name']}'",
        ]);

        $this->delete(route('superadmin.backups.destroy', $latest['name']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFileDoesNotExist($latest['path']);

        $this->get(route('superadmin.dashboard'))
            ->assertOk()
            ->assertSee('Backup History')
            ->assertSee('Created Database Backup')
            ->assertSee('Downloaded Database Backup')
            ->assertSee('Deleted Database Backup');
    }

    public function test_admin_dashboard_does_not_show_backups_or_access_logs(): void
    {
        $this->actingAs($this->admin())
            ->get(route('adashboard'))
            ->assertOk()
            ->assertDontSee('Database Backups')
            ->assertDontSee('Backup History')
            ->assertDontSee('Access Logs')
            ->assertViewMissing('backupFiles')
            ->assertViewMissing('accessLogs');
    }

    public function test_admin_cannot_manage_backups(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('superadmin.backups.store'))
            ->assertRedirect(route('adashboard'));

        $this->get(route('superadmin.backups.download', 'sevs-backup-2026-01-01_000000.zip'))
            ->assertRedirect(route('adashboard'));
    }

    public function test_restore_requires_confirmation_phrase(): void
    {
        $this->actingAs($this->superAdmin());

        $this->post(route('superadmin.backups.store'))->assertRedirect();

        $latest = app(BackupService::class)->latest();
        $this->assertNotNull($latest);

        $this->post(route('superadmin.backups.restore', $latest['name']), [
            'confirmation' => 'NOPE',
        ])->assertSessionHasErrors('confirmation');
    }

    public function test_non_super_admin_cannot_manage_backups(): void
    {
        $registrar = User::create([
            'username' => 'registrar-backup',
            'email' => 'registrar-backup@example.com',
            'password' => bcrypt('password'),
            'role' => 'Registrar',
        ]);

        $this->actingAs($registrar);

        $this->post(route('superadmin.backups.store'))->assertRedirect(route('rdashboard'));
        $this->get(route('superadmin.backups.download', 'sevs-backup-2026-01-01_000000.zip'))
            ->assertRedirect(route('rdashboard'));
    }

    public function test_unknown_backup_file_returns_not_found(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get(route('superadmin.backups.download', 'not-a-real-backup.zip'))->assertNotFound();
    }
}
