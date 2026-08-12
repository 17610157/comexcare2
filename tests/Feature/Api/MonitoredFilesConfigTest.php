<?php

use App\Models\Computer;
use App\Models\Group;
use App\Models\MonitoredFile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns monitored_files with absolute, relative, with/without file_names and recursive', function () {
    $computer = Computer::factory()->create();

    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => 'D:\\PVSI\\AJTFLU_RESUMEN',
        'file_names' => ['ConciliacionApp.exe'],
        'recursive' => false,
        'sort_order' => 1,
    ]);
    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => 'MODEM/ATM',
        'file_names' => [],
        'recursive' => false,
        'sort_order' => 2,
    ]);
    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => 'quickbck',
        'file_names' => ['*.DBF'],
        'recursive' => false,
        'sort_order' => 3,
    ]);
    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => 'ResurCARE/CareAgentResurtido',
        'file_names' => ['*.EXE'],
        'recursive' => true,
        'sort_order' => 4,
    ]);

    $this->getJson('/api/computer/'.$computer->id.'/config')
        ->assertOk()
        ->assertJsonPath('monitored_files', [
            ['path' => 'D:\\PVSI\\AJTFLU_RESUMEN', 'file_name' => 'ConciliacionApp.exe', 'recursive' => false],
            ['path' => 'MODEM/ATM', 'file_name' => null, 'recursive' => false],
            ['path' => 'quickbck', 'file_name' => '*.DBF', 'recursive' => false],
            ['path' => 'ResurCARE/CareAgentResurtido', 'file_name' => '*.EXE', 'recursive' => true],
        ]);
});

it('expands multiple file_names into one config entry per file', function () {
    $computer = Computer::factory()->create();

    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => '.',
        'file_names' => ['*.DBF', '*.EXE', '*.CDX'],
        'recursive' => false,
        'sort_order' => 1,
    ]);

    $this->getJson('/api/computer/'.$computer->id.'/config')
        ->assertOk()
        ->assertJsonPath('monitored_files', [
            ['path' => '.', 'file_name' => '*.DBF', 'recursive' => false],
            ['path' => '.', 'file_name' => '*.EXE', 'recursive' => false],
            ['path' => '.', 'file_name' => '*.CDX', 'recursive' => false],
        ]);
});

it('inherits monitored_files from the group when the computer has none', function () {
    $group = Group::factory()->create();
    $computer = Computer::factory()->for($group)->create();

    MonitoredFile::create([
        'group_id' => $group->id,
        'path' => 'quickbck',
        'file_names' => ['*.DBF'],
        'recursive' => false,
        'sort_order' => 1,
    ]);
    MonitoredFile::create([
        'group_id' => $group->id,
        'path' => 'MODEM/ATM',
        'file_names' => [],
        'recursive' => false,
        'sort_order' => 2,
    ]);

    $this->getJson('/api/computer/'.$computer->id.'/config')
        ->assertOk()
        ->assertJsonPath('monitored_files', [
            ['path' => 'quickbck', 'file_name' => '*.DBF', 'recursive' => false],
            ['path' => 'MODEM/ATM', 'file_name' => null, 'recursive' => false],
        ]);
});

it('gives computer monitored_files priority over the group for the same path', function () {
    $group = Group::factory()->create();
    $computer = Computer::factory()->for($group)->create();

    MonitoredFile::create([
        'group_id' => $group->id,
        'path' => 'quickbck',
        'file_names' => ['*.DBF'],
        'recursive' => false,
        'sort_order' => 1,
    ]);
    MonitoredFile::create([
        'group_id' => $group->id,
        'path' => 'MODEM/ATM',
        'file_names' => [],
        'recursive' => false,
        'sort_order' => 2,
    ]);

    // El equipo redefine la ruta quickbck (gana la del equipo) y agrega una nueva.
    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => 'quickbck',
        'file_names' => ['*.CDX'],
        'recursive' => true,
        'sort_order' => 1,
    ]);
    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => 'comex_id',
        'file_names' => ['pvsi_bepartners.exe'],
        'recursive' => false,
        'sort_order' => 2,
    ]);

    $this->getJson('/api/computer/'.$computer->id.'/config')
        ->assertOk()
        ->assertJsonPath('monitored_files', [
            ['path' => 'quickbck', 'file_name' => '*.CDX', 'recursive' => true],
            ['path' => 'comex_id', 'file_name' => 'pvsi_bepartners.exe', 'recursive' => false],
            ['path' => 'MODEM/ATM', 'file_name' => null, 'recursive' => false],
        ]);
});

it('sorts combined monitored_files by sort_order', function () {
    $group = Group::factory()->create();
    $computer = Computer::factory()->for($group)->create();

    MonitoredFile::create([
        'group_id' => $group->id,
        'path' => 'grupo-1',
        'file_names' => [],
        'recursive' => false,
        'sort_order' => 1,
    ]);
    MonitoredFile::create([
        'group_id' => $group->id,
        'path' => 'grupo-3',
        'file_names' => [],
        'recursive' => false,
        'sort_order' => 3,
    ]);
    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => 'equipo-2',
        'file_names' => [],
        'recursive' => false,
        'sort_order' => 2,
    ]);
    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => 'equipo-4',
        'file_names' => [],
        'recursive' => false,
        'sort_order' => 4,
    ]);

    $this->getJson('/api/computer/'.$computer->id.'/config')
        ->assertOk()
        ->assertJsonPath('monitored_files', [
            ['path' => 'grupo-1', 'file_name' => null, 'recursive' => false],
            ['path' => 'equipo-2', 'file_name' => null, 'recursive' => false],
            ['path' => 'grupo-3', 'file_name' => null, 'recursive' => false],
            ['path' => 'equipo-4', 'file_name' => null, 'recursive' => false],
        ]);
});

it('includes general monitored_files for every machine with group and computer overrides', function () {
    $group = Group::factory()->create();
    $computer = Computer::factory()->for($group)->create();
    $otherComputer = Computer::factory()->create();

    MonitoredFile::create([
        'general' => true,
        'path' => 'comex_id',
        'file_names' => ['pvsi_bepartners.exe'],
        'recursive' => false,
        'sort_order' => 1,
    ]);
    MonitoredFile::create([
        'general' => true,
        'path' => 'MODEM/ATM',
        'file_names' => [],
        'recursive' => false,
        'sort_order' => 2,
    ]);
    MonitoredFile::create([
        'group_id' => $group->id,
        'path' => 'quickbck',
        'file_names' => ['*.DBF'],
        'recursive' => false,
        'sort_order' => 1,
    ]);
    // El equipo sobrescribe la ruta general MODEM/ATM (mismo path, gana el equipo).
    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => 'MODEM/ATM',
        'file_names' => ['*.EXE'],
        'recursive' => true,
        'sort_order' => 1,
    ]);
    MonitoredFile::create([
        'computer_id' => $computer->id,
        'path' => 'ResurCARE/CareAgentResurtido',
        'file_names' => ['*.EXE'],
        'recursive' => true,
        'sort_order' => 2,
    ]);

    $this->getJson('/api/computer/'.$computer->id.'/config')
        ->assertOk()
        ->assertJsonPath('monitored_files', [
            ['path' => 'MODEM/ATM', 'file_name' => '*.EXE', 'recursive' => true],
            ['path' => 'quickbck', 'file_name' => '*.DBF', 'recursive' => false],
            ['path' => 'comex_id', 'file_name' => 'pvsi_bepartners.exe', 'recursive' => false],
            ['path' => 'ResurCARE/CareAgentResurtido', 'file_name' => '*.EXE', 'recursive' => true],
        ]);

    // Una máquina sin grupo también recibe los registros generales.
    $this->getJson('/api/computer/'.$otherComputer->id.'/config')
        ->assertOk()
        ->assertJsonPath('monitored_files', [
            ['path' => 'comex_id', 'file_name' => 'pvsi_bepartners.exe', 'recursive' => false],
            ['path' => 'MODEM/ATM', 'file_name' => null, 'recursive' => false],
        ]);
});

it('returns an empty monitored_files array when nothing is configured', function () {
    $computer = Computer::factory()->create();

    $this->getJson('/api/computer/'.$computer->id.'/config')
        ->assertOk()
        ->assertJsonPath('monitored_files', []);
});

it('returns 404 for a non-existent computer on config endpoint', function () {
    $this->getJson('/api/computer/99999/config')
        ->assertNotFound();
});
