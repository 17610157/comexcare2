<?php

use App\Models\AgentDefaultCategory;
use App\Models\AgentDefaultCategoryFile;
use App\Models\AgentDefaultCategoryRoute;
use App\Models\AgentDefaultDownload;
use App\Models\AgentDefaultRouteAssignment;
use App\Models\Computer;
use App\Models\FileList;
use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'admin.ver', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'agent-defaults.ver', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'agent-defaults.crear', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'agent-defaults.editar', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'agent-defaults.eliminar', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo([
        'admin.ver',
        'agent-defaults.ver',
        'agent-defaults.crear',
        'agent-defaults.editar',
        'agent-defaults.eliminar',
    ]);

    $this->actingAs($this->admin);
    $this->withoutMiddleware(ValidateCsrfToken::class);
    Storage::fake('local');
});

it('can create a category', function () {
    $this->post(route('admin.agent-defaults.store'), [
        'name' => 'Test Category',
        'description' => 'Test Description',
    ])->assertRedirect()
        ->assertSessionHas('success', 'Categoría creada exitosamente.');
});

it('can list categories', function () {
    $category = AgentDefaultCategory::create([
        'name' => 'List Test Category',
        'description' => 'List Test',
    ]);

    $this->get(route('admin.agent-defaults.index'))
        ->assertOk()
        ->assertSee('List Test Category');
});

it('can show a category', function () {
    $category = AgentDefaultCategory::create([
        'name' => 'Show Test',
        'description' => 'Show Test Desc',
    ]);

    $this->get(route('admin.agent-defaults.show', $category))
        ->assertOk()
        ->assertSee('Show Test');
});

it('can update a category', function () {
    $category = AgentDefaultCategory::create([
        'name' => 'Original Name',
        'description' => 'Original desc',
    ]);

    $this->put(route('admin.agent-defaults.update', $category), [
        'name' => 'Updated Name',
        'description' => 'Updated desc',
        'is_active' => false,
    ])->assertRedirect()
        ->assertSessionHas('success', 'Categoría actualizada exitosamente.');
});

it('can delete a category', function () {
    $category = AgentDefaultCategory::create([
        'name' => 'Delete Test',
        'description' => 'To be deleted',
    ]);

    $this->delete(route('admin.agent-defaults.destroy', $category))
        ->assertRedirect();

    $this->get(route('admin.agent-defaults.index'))
        ->assertOk()
        ->assertDontSee('Delete Test');
});

it('can add a route to a category', function () {
    $category = AgentDefaultCategory::create(['name' => 'Route Test Cat']);

    $this->post(route('admin.agent-defaults.routes.store', $category), [
        'route_pattern' => '\\\\srv\\archivos\\test',
        'label' => 'Main Route',
        'download_path_index' => 2,
    ])->assertJson([
        'message' => 'Ruta agregada exitosamente.',
    ])->assertJsonStructure([
        'route' => ['id', 'route_pattern', 'agent_default_category_id'],
    ])->assertJsonPath('route.route_pattern', '\\\\srv\\archivos\\test');
});

it('can update a route', function () {
    $category = AgentDefaultCategory::create(['name' => 'Route Update Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    $this->put(route('admin.agent-defaults.routes.update', $route), [
        'route_pattern' => '\\\\updated\\route',
        'label' => 'Updated',
    ])->assertJson(['message' => 'Ruta actualizada exitosamente.']);
});

it('can delete a route', function () {
    $category = AgentDefaultCategory::create(['name' => 'Route Delete Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    $this->delete(route('admin.agent-defaults.routes.destroy', $route))
        ->assertJson(['message' => 'Ruta eliminada exitosamente.']);
});

it('can add a computer assignment to a route', function () {
    $category = AgentDefaultCategory::create(['name' => 'Assignment Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    $computer = Computer::factory()->create(['plaza' => '01']);

    $this->post(route('admin.agent-defaults.assignments.store', $route), [
        'assignable_type' => 'computer',
        'assignable_id' => $computer->id,
    ])->assertJson([
        'message' => 'Asignación agregada exitosamente.',
    ])->assertJsonStructure([
        'assignment' => ['id', 'assignable_type', 'assignable_id'],
    ]);
});

it('can add a group assignment to a route', function () {
    $category = AgentDefaultCategory::create(['name' => 'Group Assignment Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    $group = Group::factory()->create();

    $this->post(route('admin.agent-defaults.assignments.store', $route), [
        'assignable_type' => 'group',
        'assignable_id' => $group->id,
    ])->assertJson([
        'message' => 'Asignación agregada exitosamente.',
    ])->assertJsonStructure([
        'assignment' => ['id', 'assignable_type', 'assignable_id'],
    ]);
});

it('prevents duplicate assignments', function () {
    $category = AgentDefaultCategory::create(['name' => 'Dup Assignment Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    $computer = Computer::factory()->create(['plaza' => '01']);

    AgentDefaultRouteAssignment::factory()
        ->for($route, 'route')
        ->create([
            'assignable_type' => Computer::class,
            'assignable_id' => $computer->id,
        ]);

    $this->post(route('admin.agent-defaults.assignments.store', $route), [
        'assignable_type' => 'computer',
        'assignable_id' => $computer->id,
    ])->assertStatus(422)->assertJson(['message' => 'Esta asignación ya existe.']);
});

it('can delete an assignment', function () {
    $category = AgentDefaultCategory::create(['name' => 'Del Assignment Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    $assignment = AgentDefaultRouteAssignment::factory()
        ->for($route, 'route')
        ->create();

    $this->delete(route('admin.agent-defaults.assignments.destroy', $assignment))
        ->assertJson(['message' => 'Asignación eliminada exitosamente.']);
});

it('can upload a file to a route', function () {
    $category = AgentDefaultCategory::create(['name' => 'File Upload Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    FileList::where('type', 'whitelist')->delete();

    $file = UploadedFile::fake()->create('test.txt', 100);

    $this->post(route('admin.agent-defaults.files.store', $route), [
        'file' => $file,
    ])->assertJson([
        'message' => 'Archivo subido exitosamente.',
    ])->assertJsonStructure([
        'file' => ['id', 'file_name', 'checksum', 'file_size'],
    ]);
});

it('can delete a file from a route', function () {
    $category = AgentDefaultCategory::create(['name' => 'File Delete Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    $categoryFile = AgentDefaultCategoryFile::factory()
        ->for($route, 'route')
        ->create();

    $this->delete(route('admin.agent-defaults.files.destroy', [$route, $categoryFile]))
        ->assertJson(['message' => 'Archivo eliminado exitosamente.']);
});

it('can download a file', function () {
    $category = AgentDefaultCategory::create(['name' => 'File Download Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    $categoryFile = AgentDefaultCategoryFile::factory()
        ->for($route, 'route')
        ->create([
            'file_path' => 'agent_defaults/1/routes/1/test.txt',
        ]);

    Storage::put('agent_defaults/1/routes/1/test.txt', 'test content');

    $this->get(route('admin.agent-defaults.files.download', [$route, $categoryFile]))
        ->assertOk();
});

it('validates files against blacklist', function () {
    $category = AgentDefaultCategory::create(['name' => 'Blacklist Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    FileList::firstOrCreate([
        'type' => 'blacklist',
        'file_name' => '.exe',
    ], ['created_by' => $this->admin->id]);

    $file = UploadedFile::fake()->create('virus.exe', 100);

    $this->post(route('admin.agent-defaults.files.store', $route), [
        'file' => $file,
    ])->assertStatus(422)->assertJson([
        'message' => "El archivo 'virus.exe' está en la blacklist y no puede ser subido.",
    ]);
});

it('validates files against whitelist', function () {
    $category = AgentDefaultCategory::create(['name' => 'Whitelist Cat']);
    $route = AgentDefaultCategoryRoute::factory()
        ->for($category, 'category')
        ->create();

    FileList::firstOrCreate([
        'type' => 'whitelist',
        'file_name' => '.pdf',
    ], ['created_by' => $this->admin->id]);

    $file = UploadedFile::fake()->create('document.txt', 100);

    $this->post(route('admin.agent-defaults.files.store', $route), [
        'file' => $file,
    ])->assertStatus(422)->assertJson([
        'message' => "El archivo 'document.txt' no está en la whitelist y no puede ser subido.",
    ]);
});

// -----------------------------------------------------------------------
// API endpoint: GET /api/agent-defaults/config/{computerId}
// -----------------------------------------------------------------------

it('returns config for a computer with direct assignment', function () {
    $computer = Computer::factory()->create();
    $category = AgentDefaultCategory::create(['name' => 'PDF Formatos', 'is_active' => true]);
    $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create(['route_pattern' => '\\\\srv\\formatos']);
    AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
        'assignable_type' => Computer::class,
        'assignable_id' => $computer->id,
    ]);
    $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
        'file_name' => 'contrato.pdf',
        'checksum' => 'abc123',
        'file_size' => 2048,
    ]);

    $this->getJson('/api/agent-defaults/config/'.$computer->id)
        ->assertOk()
        ->assertJsonStructure([
            'categories' => [
                '*' => [
                    'id', 'name', 'auto_sync', 'auto_validation', 'routes' => [
                        '*' => [
                            'id', 'route_pattern', 'label', 'download_path_index', 'sort_order', 'ruta_servidor',
                            'files' => ['*' => ['id', 'file_name', 'checksum', 'file_size', 'ruta_servidor']],
                        ],
                    ],
                ],
            ],
        ])
        ->assertJsonPath('categories.0.name', 'PDF Formatos')
        ->assertJsonPath('categories.0.routes.0.files.0.file_name', 'contrato.pdf')
        ->assertJsonPath('categories.0.routes.0.files.0.ruta_servidor', '\\\\srv\\formatos\\contrato.pdf');
});

it('returns config for a computer via group membership', function () {
    $group = Group::factory()->create();
    $computer = Computer::factory()->for($group)->create();
    $category = AgentDefaultCategory::create(['name' => 'Group Config', 'is_active' => true]);
    $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create();
    AgentDefaultRouteAssignment::factory()->for($route, 'route')->forGroup()->create([
        'assignable_type' => Group::class,
        'assignable_id' => $group->id,
    ]);

    $this->getJson('/api/agent-defaults/config/'.$computer->id)
        ->assertOk()
        ->assertJsonCount(1, 'categories');
});

it('returns categories with empty routes when computer has no assignments', function () {
    $computer = Computer::factory()->create();
    AgentDefaultCategory::create(['name' => 'No Assign', 'is_active' => true]);

    $this->getJson('/api/agent-defaults/config/'.$computer->id)
        ->assertOk()
        ->assertJsonCount(1, 'categories')
        ->assertJsonPath('categories.0.routes', []);
});

it('returns 404 for non-existent computer on config endpoint', function () {
    $this->getJson('/api/agent-defaults/config/99999')
        ->assertNotFound();
});

it('only returns active categories in config', function () {
    $computer = Computer::factory()->create();
    $active = AgentDefaultCategory::create(['name' => 'Active Cat', 'is_active' => true]);
    $inactive = AgentDefaultCategory::create(['name' => 'Inactive Cat', 'is_active' => false]);

    foreach ([$active, $inactive] as $cat) {
        $route = AgentDefaultCategoryRoute::factory()->for($cat, 'category')->create();
        AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
            'assignable_type' => Computer::class,
            'assignable_id' => $computer->id,
        ]);
    }

    $this->getJson('/api/agent-defaults/config/'.$computer->id)
        ->assertOk()
        ->assertJsonCount(1, 'categories')
        ->assertJsonPath('categories.0.name', 'Active Cat');
});

it('returns all active categories in config regardless of auto_sync', function () {
    $computer = Computer::factory()->create();
    $syncOn = AgentDefaultCategory::create(['name' => 'Auto Sync On', 'is_active' => true, 'auto_sync' => true]);
    $syncOff = AgentDefaultCategory::create(['name' => 'Auto Sync Off', 'is_active' => true, 'auto_sync' => false]);
    $inactive = AgentDefaultCategory::create(['name' => 'Inactive', 'is_active' => false, 'auto_sync' => true]);

    foreach ([$syncOn, $syncOff] as $cat) {
        $route = AgentDefaultCategoryRoute::factory()->for($cat, 'category')->create();
        AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
            'assignable_type' => Computer::class,
            'assignable_id' => $computer->id,
        ]);
    }

    $this->getJson('/api/agent-defaults/config/'.$computer->id)
        ->assertOk()
        ->assertJsonCount(2, 'categories')
        ->assertJsonPath('categories.0.name', 'Auto Sync On')
        ->assertJsonPath('categories.0.auto_sync', true)
        ->assertJsonPath('categories.0.auto_validation', true)
        ->assertJsonPath('categories.1.name', 'Auto Sync Off')
        ->assertJsonPath('categories.1.auto_sync', false)
        ->assertJsonPath('categories.1.auto_validation', true);
});

// -----------------------------------------------------------------------
// API endpoint: POST /api/agent-defaults/sync-status
// -----------------------------------------------------------------------

it('reports sync status for a file', function () {
    $computer = Computer::factory()->create();
    $file = AgentDefaultCategoryFile::factory()->create();

    $this->postJson('/api/agent-defaults/sync-status', [
        'computer_id' => $computer->id,
        'files' => [
            [
                'file_id' => $file->id,
                'sync_status' => 'synced',
                'local_path' => 'C:\\agent\\files\\test.pdf',
                'local_checksum' => 'local_sha256',
                'ruta_local' => 'C:\\agent\\files\\test.pdf',
                'ruta_servidor' => '\\\\srv\\formatos\\test.pdf',
            ],
        ],
    ])->assertOk()
        ->assertJson([
            'message' => 'Sync status updated',
            'files' => [['file_id' => $file->id, 'status' => 'synced']],
        ]);

    $this->assertDatabaseHas('agent_default_downloads', [
        'computer_id' => $computer->id,
        'agent_default_category_file_id' => $file->id,
        'sync_status' => 'synced',
        'local_checksum' => 'local_sha256',
    ]);
});

it('upserts sync status on repeated reports', function () {
    $computer = Computer::factory()->create();
    $file = AgentDefaultCategoryFile::factory()->create();

    // First report: different
    $this->postJson('/api/agent-defaults/sync-status', [
        'computer_id' => $computer->id,
        'files' => [
            [
                'file_id' => $file->id,
                'sync_status' => 'different',
                'local_path' => 'C:\\agent\\files\\test.pdf',
                'local_checksum' => 'old_sha256',
                'ruta_local' => 'C:\\agent\\files\\test.pdf',
                'ruta_servidor' => '\\\\srv\\formatos\\test.pdf',
            ],
        ],
    ])->assertOk();

    // Second report: synced (upsert)
    $this->postJson('/api/agent-defaults/sync-status', [
        'computer_id' => $computer->id,
        'files' => [
            [
                'file_id' => $file->id,
                'sync_status' => 'synced',
                'local_path' => 'C:\\agent\\files\\test.pdf',
                'local_checksum' => 'new_sha256',
                'ruta_local' => 'C:\\agent\\files\\test.pdf',
                'ruta_servidor' => '\\\\srv\\formatos\\test.pdf',
            ],
        ],
    ])->assertOk();

    $this->assertDatabaseHas('agent_default_downloads', [
        'computer_id' => $computer->id,
        'agent_default_category_file_id' => $file->id,
        'sync_status' => 'synced',
        'local_checksum' => 'new_sha256',
    ]);

    $this->assertDatabaseCount('agent_default_downloads', 1);
});

it('handles multiple files in a single sync report', function () {
    $computer = Computer::factory()->create();
    $fileA = AgentDefaultCategoryFile::factory()->create();
    $fileB = AgentDefaultCategoryFile::factory()->create();

    $this->postJson('/api/agent-defaults/sync-status', [
        'computer_id' => $computer->id,
        'files' => [
            [
                'file_id' => $fileA->id,
                'sync_status' => 'synced',
                'local_path' => 'C:\\files\\a.pdf',
                'local_checksum' => 'sha_a',
                'ruta_local' => 'C:\\files\\a.pdf',
                'ruta_servidor' => '\\\\srv\\a.pdf',
            ],
            [
                'file_id' => $fileB->id,
                'sync_status' => 'error',
                'local_path' => 'C:\\files\\b.pdf',
                'local_checksum' => '',
                'ruta_local' => 'C:\\files\\b.pdf',
                'ruta_servidor' => '\\\\srv\\b.pdf',
            ],
        ],
    ])->assertOk()
        ->assertJson([
            'message' => 'Sync status updated',
            'files' => [
                ['file_id' => $fileA->id, 'status' => 'synced'],
                ['file_id' => $fileB->id, 'status' => 'error'],
            ],
        ]);
});

it('validates required fields on sync-status endpoint', function () {
    $this->postJson('/api/agent-defaults/sync-status', [])
        ->assertStatus(422);

    $this->postJson('/api/agent-defaults/sync-status', [
        'computer_id' => 99999,
        'files' => [],
    ])->assertStatus(422);

    $this->postJson('/api/agent-defaults/sync-status', [
        'computer_id' => 1,
        'files' => [
            ['file_id' => 99999, 'sync_status' => 'invalid'],
        ],
    ])->assertStatus(422);
});

it('supports all sync status values', function () {
    $computer = Computer::factory()->create();
    $file = AgentDefaultCategoryFile::factory()->create();

    foreach (['synced', 'different', 'error', 'pending'] as $status) {
        $this->postJson('/api/agent-defaults/sync-status', [
            'computer_id' => $computer->id,
            'files' => [
                [
                    'file_id' => $file->id,
                    'sync_status' => $status,
                    'ruta_local' => 'C:\\test.pdf',
                    'ruta_servidor' => '\\\\srv\\test.pdf',
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('files.0.status', $status);
    }
});

// -----------------------------------------------------------------------
// API endpoint: GET /api/agent-defaults/download/{fileId}
// -----------------------------------------------------------------------

it('downloads a file with checksum headers', function () {
    $category = AgentDefaultCategory::create(['name' => 'Download Cat']);
    $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create();
    $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
        'file_path' => 'agent_defaults/downloads/test.txt',
        'checksum' => 'abc123checksum',
    ]);
    Storage::put('agent_defaults/downloads/test.txt', 'file content');

    $response = $this->getJson('/api/agent-defaults/download/'.$file->id);

    $response->assertOk();
    $response->assertHeader('X-Checksum', 'abc123checksum');
});

it('returns 404 when downloading a non-existent file', function () {
    $this->getJson('/api/agent-defaults/download/99999')
        ->assertNotFound();
});

it('returns 404 when file storage path is missing', function () {
    $file = AgentDefaultCategoryFile::factory()->create([
        'file_path' => 'agent_defaults/missing/nope.txt',
    ]);

    $this->getJson('/api/agent-defaults/download/'.$file->id)
        ->assertNotFound();
});

// -----------------------------------------------------------------------
// Show view: sync tab status indicators
// -----------------------------------------------------------------------

it('shows pending status for files not yet reported by agent', function () {
    $computer = Computer::factory()->create(['computer_name' => 'PC-OFICINA1']);
    $category = AgentDefaultCategory::create(['name' => 'Sync Test']);
    $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create(['route_pattern' => '\\\\srv\\formatos']);
    AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
        'assignable_type' => Computer::class,
        'assignable_id' => $computer->id,
    ]);
    $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
        'file_name' => 'contrato.pdf',
        'checksum' => 'server_sha',
    ]);

    $this->get(route('admin.agent-defaults.show', $category))
        ->assertOk()
        ->assertSee('PC-OFICINA1')
        ->assertSee('contrato.pdf')
        ->assertSee('server_sha')
        ->assertSee('Pendiente');
});

it('shows synced status after agent reports', function () {
    $computer = Computer::factory()->create(['computer_name' => 'PC-OFICINA1']);
    $category = AgentDefaultCategory::create(['name' => 'Sync Test']);
    $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create(['route_pattern' => '\\\\srv\\formatos']);
    AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
        'assignable_type' => Computer::class,
        'assignable_id' => $computer->id,
    ]);
    $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
        'file_name' => 'contrato.pdf',
        'checksum' => 'server_sha',
    ]);

    // Agent reports synced
    AgentDefaultDownload::create([
        'computer_id' => $computer->id,
        'agent_default_category_file_id' => $file->id,
        'sync_status' => 'synced',
        'local_path' => 'C:\\agent\\contrato.pdf',
        'local_checksum' => 'server_sha',
        'ruta_local' => 'C:\\agent\\contrato.pdf',
        'ruta_servidor' => '\\\\srv\\formatos\\contrato.pdf',
        'synced_at' => now(),
    ]);

    $this->get(route('admin.agent-defaults.show', $category))
        ->assertOk()
        ->assertSee('PC-OFICINA1')
        ->assertSee('contrato.pdf')
        ->assertSee('Actualizado');
});

it('shows different status when local checksum differs from server', function () {
    $computer = Computer::factory()->create(['computer_name' => 'PC-OFICINA1']);
    $category = AgentDefaultCategory::create(['name' => 'Sync Diff Test']);
    $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create(['route_pattern' => '\\\\srv\\formatos']);
    AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
        'assignable_type' => Computer::class,
        'assignable_id' => $computer->id,
    ]);
    $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
        'file_name' => 'contrato.pdf',
        'checksum' => 'server_sha',
    ]);

    AgentDefaultDownload::create([
        'computer_id' => $computer->id,
        'agent_default_category_file_id' => $file->id,
        'sync_status' => 'different',
        'local_path' => 'C:\\agent\\contrato.pdf',
        'local_checksum' => 'different_local_sha',
        'ruta_local' => 'C:\\agent\\contrato.pdf',
        'ruta_servidor' => '\\\\srv\\formatos\\contrato.pdf',
    ]);

    $this->get(route('admin.agent-defaults.show', $category))
        ->assertOk()
        ->assertSee('Diferente');
});

it('shows error status when agent reports error', function () {
    $computer = Computer::factory()->create(['computer_name' => 'PC-OFICINA1']);
    $category = AgentDefaultCategory::create(['name' => 'Sync Err Test']);
    $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create();
    AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
        'assignable_type' => Computer::class,
        'assignable_id' => $computer->id,
    ]);
    $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create();

    AgentDefaultDownload::create([
        'computer_id' => $computer->id,
        'agent_default_category_file_id' => $file->id,
        'sync_status' => 'error',
    ]);

    $this->get(route('admin.agent-defaults.show', $category))
        ->assertOk()
        ->assertSee('Error');
});

it('expands group assignments to member computers in sync tab', function () {
    $group = Group::factory()->create();
    $computerA = Computer::factory()->for($group)->create(['computer_name' => 'PC-A']);
    $computerB = Computer::factory()->for($group)->create(['computer_name' => 'PC-B']);
    $category = AgentDefaultCategory::create(['name' => 'Group Sync']);
    $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create();
    AgentDefaultRouteAssignment::factory()->for($route, 'route')->forGroup()->create([
        'assignable_type' => Group::class,
        'assignable_id' => $group->id,
    ]);
    $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create(['file_name' => 'shared.pdf']);

    $this->get(route('admin.agent-defaults.show', $category))
        ->assertOk()
        ->assertSee('PC-A')
        ->assertSee('PC-B')
        ->assertSee('shared.pdf');
});

it('shows server and local checksums in sync tab', function () {
    $computer = Computer::factory()->create(['computer_name' => 'PC-OFICINA1']);
    $category = AgentDefaultCategory::create(['name' => 'Checksum Test']);
    $route = AgentDefaultCategoryRoute::factory()->for($category, 'category')->create();
    AgentDefaultRouteAssignment::factory()->for($route, 'route')->create([
        'assignable_type' => Computer::class,
        'assignable_id' => $computer->id,
    ]);
    $file = AgentDefaultCategoryFile::factory()->for($route, 'route')->create([
        'file_name' => 'data.bin',
        'checksum' => 'server_checksum_123',
    ]);

    AgentDefaultDownload::create([
        'computer_id' => $computer->id,
        'agent_default_category_file_id' => $file->id,
        'sync_status' => 'synced',
        'local_checksum' => 'local_checksum_456',
        'ruta_local' => 'C:\\data.bin',
        'ruta_servidor' => '\\\\srv\\data.bin',
    ]);

    $this->get(route('admin.agent-defaults.show', $category))
        ->assertOk()
        ->assertSee('server_checksum_123')
        ->assertSee('local_checks');
});
