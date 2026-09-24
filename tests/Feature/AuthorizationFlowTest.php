<?php

use App\Models\AuthorizableEmail;
use App\Models\AuthorizationToken;
use App\Models\FileList;
use App\Models\FileListAuthorization;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;

it('creates a file list with pending status', function () {
    $user = User::factory()->create(['activo' => true]);
    $module = Module::create(['name' => 'Test', 'slug' => 'test', 'is_active' => true]);

    Permission::firstOrCreate(['name' => 'admin.ver', 'guard_name' => 'web']);
    $user->givePermissionTo('admin.ver');

    $this->actingAs($user);

    Notification::fake();

    $response = $this->postJson(route('admin.file-lists.store'), [
        'type' => 'whitelist',
        'file_name' => 'test.xlsx',
        'description' => 'Test file',
        'module_id' => $module->id,
    ]);

    $response->assertSuccessful();

    $fileList = FileList::where('file_name', 'test.xlsx')->first();
    expect($fileList->status)->toBe('pending');
    expect($fileList->module_id)->toBe($module->id);
});

it('creates a file list with uploaded file and stores MD5', function () {
    Storage::fake('local');

    $user = User::factory()->create(['activo' => true]);
    $module = Module::create(['name' => 'Upload', 'slug' => 'upload-test', 'is_active' => true]);

    Permission::firstOrCreate(['name' => 'admin.ver', 'guard_name' => 'web']);
    $user->givePermissionTo('admin.ver');

    $this->actingAs($user);

    Notification::fake();

    $file = UploadedFile::fake()->createWithContent('test-upload.exe', 'fake content here');

    $response = $this->postJson(route('admin.file-lists.store'), [
        'type' => 'whitelist',
        'file_name' => 'test-upload.exe',
        'file' => $file,
        'description' => 'File with MD5',
        'module_id' => $module->id,
    ]);

    $response->assertSuccessful();

    $fileList = FileList::where('file_name', 'test-upload.exe')->first();
    expect($fileList)->not->toBeNull();
    expect($fileList->status)->toBe('pending');
    expect($fileList->file_md5)->not->toBeNull();
    expect(strlen($fileList->file_md5))->toBe(32);
    expect($fileList->file_path)->not->toBeNull();
    expect($fileList->file_size)->toBeGreaterThan(0);
    expect($fileList->hasAttachment())->toBeTrue();
});

it('can download file via authorization token', function () {
    Storage::fake('local');

    $user = User::factory()->create(['activo' => true]);
    $module = Module::create(['name' => 'DL', 'slug' => 'dl-test', 'is_active' => true]);

    $content = 'test file content for download';
    $file = UploadedFile::fake()->createWithContent('dl-test.xlsx', $content);
    $md5 = md5_file($file->getRealPath());

    $path = $file->store('file-lists', 'local');

    $fileList = FileList::create([
        'type' => 'whitelist',
        'file_name' => 'dl-test.xlsx',
        'file_path' => $path,
        'file_md5' => $md5,
        'file_size' => $file->getSize(),
        'description' => 'Download test',
        'created_by' => $user->id,
        'status' => 'pending',
        'module_id' => $module->id,
    ]);

    $authEmail = AuthorizableEmail::create([
        'user_id' => $user->id,
        'email' => 'dl@test.com',
        'module_id' => $module->id,
        'is_active' => true,
    ]);

    $token = AuthorizationToken::create([
        'file_list_id' => $fileList->id,
        'authorizable_email_id' => $authEmail->id,
        'token' => AuthorizationToken::generate(),
        'expires_at' => now()->addHours(48),
    ]);

    $response = $this->get(route('authorization.file', $token->token));
    $response->assertStatus(200);
    $response->assertHeader('content-disposition', 'attachment; filename=dl-test.xlsx');
});

it('shows hash and download link on authorization page', function () {
    Storage::fake('local');

    $user = User::factory()->create(['activo' => true]);
    $module = Module::create(['name' => 'ViewHash', 'slug' => 'viewhash-test', 'is_active' => true]);

    $content = 'view hash content';
    $file = UploadedFile::fake()->createWithContent('vh-test.xlsx', $content);
    $md5 = md5_file($file->getRealPath());
    $path = $file->store('file-lists', 'local');

    $fileList = FileList::create([
        'type' => 'blacklist',
        'file_name' => 'vh-test.xlsx',
        'file_path' => $path,
        'file_md5' => $md5,
        'file_size' => $file->getSize(),
        'description' => 'View hash test',
        'created_by' => $user->id,
        'status' => 'pending',
        'module_id' => $module->id,
    ]);

    $authEmail = AuthorizableEmail::create([
        'user_id' => $user->id,
        'email' => 'vh@test.com',
        'module_id' => $module->id,
        'is_active' => true,
    ]);

    $token = AuthorizationToken::create([
        'file_list_id' => $fileList->id,
        'authorizable_email_id' => $authEmail->id,
        'token' => AuthorizationToken::generate(),
        'expires_at' => now()->addHours(48),
    ]);

    $response = $this->get(route('authorization.show', $token->token));
    $response->assertSuccessful();
    $response->assertSee($md5);
    $response->assertSee('Descargar archivo para revisar');
});

it('does not validate pending files in validateFiles endpoint', function () {
    $user = User::factory()->create(['activo' => true]);

    Permission::firstOrCreate(['name' => 'admin.ver', 'guard_name' => 'web']);
    $user->givePermissionTo('admin.ver');

    $this->actingAs($user);

    FileList::create([
        'type' => 'blacklist',
        'file_name' => 'virus.exe',
        'description' => 'Blocked',
        'created_by' => $user->id,
        'status' => 'pending',
    ]);

    $response = $this->postJson(route('admin.file-lists.validate'), [
        'files' => ['virus.exe'],
    ]);

    $response->assertSuccessful();
    expect($response->json('blacklisted'))->toBeEmpty();
});

it('validates active files in validateFiles endpoint', function () {
    $user = User::factory()->create(['activo' => true]);

    Permission::firstOrCreate(['name' => 'admin.ver', 'guard_name' => 'web']);
    $user->givePermissionTo('admin.ver');

    $this->actingAs($user);

    FileList::create([
        'type' => 'blacklist',
        'file_name' => 'virus.exe',
        'description' => 'Blocked',
        'created_by' => $user->id,
        'status' => 'active',
    ]);

    $response = $this->postJson(route('admin.file-lists.validate'), [
        'files' => ['virus.exe'],
    ]);

    $response->assertSuccessful();
    expect($response->json('blacklisted'))->toContain('virus.exe');
});

it('authorizes a file list via token', function () {
    $user = User::factory()->create(['activo' => true]);
    $module = Module::create(['name' => 'Test', 'slug' => 'test-auth', 'is_active' => true]);

    $fileList = FileList::create([
        'type' => 'whitelist',
        'file_name' => 'autorizado.xlsx',
        'description' => 'Needs auth',
        'created_by' => $user->id,
        'status' => 'pending',
        'module_id' => $module->id,
    ]);

    $authEmail = AuthorizableEmail::create([
        'user_id' => $user->id,
        'email' => 'auth@test.com',
        'module_id' => $module->id,
        'is_active' => true,
    ]);

    $token = AuthorizationToken::create([
        'file_list_id' => $fileList->id,
        'authorizable_email_id' => $authEmail->id,
        'token' => AuthorizationToken::generate(),
        'expires_at' => now()->addHours(48),
    ]);

    $response = $this->postJson(route('authorization.process', $token->token));

    $response->assertSuccessful();

    $fileList->refresh();
    expect($fileList->status)->toBe('active');

    $authorization = FileListAuthorization::where('file_list_id', $fileList->id)->first();
    expect($authorization)->not->toBeNull();
    expect($authorization->email)->toBe('auth@test.com');
    expect($authorization->authorized_at)->not->toBeNull();
});

it('rejects authorization with invalid token', function () {
    $response = $this->postJson(route('authorization.process', 'invalid-token'));

    $response->assertStatus(404);
});

it('rejects authorization with expired token', function () {
    $user = User::factory()->create(['activo' => true]);
    $module = Module::create(['name' => 'TestExp', 'slug' => 'test-exp', 'is_active' => true]);

    $fileList = FileList::create([
        'type' => 'whitelist',
        'file_name' => 'expired.xlsx',
        'created_by' => $user->id,
        'status' => 'pending',
        'module_id' => $module->id,
    ]);

    $authEmail = AuthorizableEmail::create([
        'user_id' => $user->id,
        'email' => 'auth@test.com',
        'module_id' => $module->id,
        'is_active' => true,
    ]);

    $token = AuthorizationToken::create([
        'file_list_id' => $fileList->id,
        'authorizable_email_id' => $authEmail->id,
        'token' => AuthorizationToken::generate(),
        'expires_at' => now()->subHour(),
    ]);

    $response = $this->postJson(route('authorization.process', $token->token));

    $response->assertStatus(410);
});

it('invalidates other tokens after successful authorization', function () {
    $user = User::factory()->create(['activo' => true]);
    $module = Module::create(['name' => 'Test3', 'slug' => 'test3', 'is_active' => true]);

    $fileList = FileList::create([
        'type' => 'blacklist',
        'file_name' => 'once.xlsx',
        'created_by' => $user->id,
        'status' => 'pending',
        'module_id' => $module->id,
    ]);

    $authEmail1 = AuthorizableEmail::create([
        'user_id' => $user->id,
        'email' => 'auth1@test.com',
        'module_id' => $module->id,
        'is_active' => true,
    ]);

    $authEmail2 = AuthorizableEmail::create([
        'user_id' => $user->id,
        'email' => 'auth2@test.com',
        'module_id' => $module->id,
        'is_active' => true,
    ]);

    $token1 = AuthorizationToken::create([
        'file_list_id' => $fileList->id,
        'authorizable_email_id' => $authEmail1->id,
        'token' => AuthorizationToken::generate(),
        'expires_at' => now()->addHours(48),
    ]);

    $token2 = AuthorizationToken::create([
        'file_list_id' => $fileList->id,
        'authorizable_email_id' => $authEmail2->id,
        'token' => AuthorizationToken::generate(),
        'expires_at' => now()->addHours(48),
    ]);

    $this->postJson(route('authorization.process', $token1->token))->assertSuccessful();

    $token2->refresh();
    expect($token2->used_at)->not->toBeNull();

    $response = $this->postJson(route('authorization.process', $token2->token));
    $response->assertStatus(410);
});

it('shows authorization page with valid token', function () {
    $user = User::factory()->create(['activo' => true]);
    $module = Module::create(['name' => 'TestView', 'slug' => 'test-view', 'is_active' => true]);

    $fileList = FileList::create([
        'type' => 'whitelist',
        'file_name' => 'viewable.xlsx',
        'created_by' => $user->id,
        'status' => 'pending',
        'module_id' => $module->id,
    ]);

    $authEmail = AuthorizableEmail::create([
        'user_id' => $user->id,
        'email' => 'auth@test.com',
        'module_id' => $module->id,
        'is_active' => true,
    ]);

    $token = AuthorizationToken::create([
        'file_list_id' => $fileList->id,
        'authorizable_email_id' => $authEmail->id,
        'token' => AuthorizationToken::generate(),
        'expires_at' => now()->addHours(48),
    ]);

    $response = $this->get(route('authorization.show', $token->token));

    $response->assertSuccessful();
    $response->assertSee('viewable.xlsx');
    $response->assertSee('Autorizar Registro');
    $response->assertSee('auth@test.com');
});

it('shows unauthorized page with invalid token', function () {
    $response = $this->get(route('authorization.show', 'bad-token'));

    $response->assertSuccessful();
    $response->assertSee('no válido');
});

it('manages modules CRUD', function () {
    $user = User::factory()->create(['activo' => true]);

    foreach (['admin.ver', 'modules.ver', 'modules.crear', 'modules.editar', 'modules.eliminar'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    $user->givePermissionTo(['admin.ver', 'modules.ver', 'modules.crear', 'modules.editar', 'modules.eliminar']);

    $this->actingAs($user);

    $response = $this->postJson(route('admin.modules.store'), [
        'name' => 'New Module',
        'slug' => 'new-module',
        'description' => 'A new module',
    ]);

    $response->assertSuccessful();

    $module = Module::where('slug', 'new-module')->first();
    expect($module)->not->toBeNull();

    $response = $this->putJson(route('admin.modules.update', $module->id), [
        'name' => 'Updated Module',
        'description' => 'Updated',
        'is_active' => true,
    ]);

    $response->assertSuccessful();
    $module->refresh();
    expect($module->name)->toBe('Updated Module');

    $response = $this->deleteJson(route('admin.modules.destroy', $module->id));
    $response->assertSuccessful();
    expect(Module::where('slug', 'new-module')->exists())->toBeFalse();
});

it('manages authorizable emails CRUD', function () {
    $user = User::factory()->create(['activo' => true]);
    $module = Module::create(['name' => 'Email Module', 'slug' => 'email-module', 'is_active' => true]);

    foreach (['admin.ver', 'authorizable-emails.ver', 'authorizable-emails.crear', 'authorizable-emails.editar', 'authorizable-emails.eliminar'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }
    $user->givePermissionTo(['admin.ver', 'authorizable-emails.ver', 'authorizable-emails.crear', 'authorizable-emails.editar', 'authorizable-emails.eliminar']);

    $this->actingAs($user);

    $response = $this->postJson(route('admin.authorizable-emails.store'), [
        'user_id' => $user->id,
        'email' => 'test@example.com',
        'module_id' => $module->id,
    ]);

    $response->assertSuccessful();

    $email = AuthorizableEmail::where('email', 'test@example.com')->first();
    expect($email)->not->toBeNull();

    $response = $this->putJson(route('admin.authorizable-emails.update', $email->id), [
        'user_id' => $user->id,
        'email' => 'updated@example.com',
        'module_id' => $module->id,
        'is_active' => true,
    ]);

    $response->assertSuccessful();

    $response = $this->deleteJson(route('admin.authorizable-emails.destroy', $email->id));
    $response->assertSuccessful();
});
