<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;
use ZipArchive;

class AdminBackupTest extends TestCase
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

    private function admin(): User
    {
        return User::create([
            'username' => 'admin-backup',
            'email' => 'admin-backup@example.com',
            'password' => bcrypt('password'),
            'role' => 'Admin',
        ]);
    }

    public function test_admin_can_create_download_and_delete_a_backup(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.backups.store'))
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

        $this->get(route('adashboard'))
            ->assertOk()
            ->assertSee($latest['name']);

        $this->get(route('admin.backups.download', $latest['name']))
            ->assertOk()
            ->assertDownload($latest['name']);

        $this->delete(route('admin.backups.destroy', $latest['name']))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFileDoesNotExist($latest['path']);
    }

    public function test_admin_dashboard_shows_the_backup_panel(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('adashboard'))
            ->assertOk()
            ->assertSee('Database Backups')
            ->assertSee('Backup Now')
            ->assertSee('Keep')
            ->assertSee('Restore');
    }

    public function test_restore_requires_confirmation_phrase(): void
    {
        $this->actingAs($this->admin());

        $this->post(route('admin.backups.store'))->assertRedirect();

        $latest = app(BackupService::class)->latest();
        $this->assertNotNull($latest);

        $this->post(route('admin.backups.restore', $latest['name']), [
            'confirmation' => 'NOPE',
        ])->assertSessionHasErrors('confirmation');
    }

    public function test_non_admin_cannot_manage_backups(): void
    {
        $registrar = User::create([
            'username' => 'registrar-backup',
            'email' => 'registrar-backup@example.com',
            'password' => bcrypt('password'),
            'role' => 'Registrar',
        ]);

        $this->actingAs($registrar);

        $this->post(route('admin.backups.store'))->assertRedirect(route('rdashboard'));
        $this->get(route('admin.backups.download', 'sevs-backup-2026-01-01_000000.zip'))
            ->assertRedirect(route('rdashboard'));
    }

    public function test_unknown_backup_file_returns_not_found(): void
    {
        $this->actingAs($this->admin());

        $this->get(route('admin.backups.download', 'not-a-real-backup.zip'))->assertNotFound();
    }
}
