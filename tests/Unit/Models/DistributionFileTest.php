<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\DistributionFile;
use App\Models\Distribution;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DistributionFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_fillable_attributes()
    {
        $distributionFile = new DistributionFile();
        
        $fillable = ['distribution_id', 'file_name', 'file_path', 'checksum', 'file_size'];
        
        foreach ($fillable as $attribute) {
            $this->assertTrue(in_array($attribute, $distributionFile->getFillable()));
        }
    }

    public function test_distribution_relationship()
    {
        $distribution = Distribution::factory()->create();
        $distributionFile = DistributionFile::factory()->create(['distribution_id' => $distribution->id]);

        $this->assertInstanceOf(Distribution::class, $distributionFile->distribution);
        $this->assertEquals($distribution->id, $distributionFile->distribution->id);
    }

    public function test_mass_assignment()
    {
        $distribution = Distribution::factory()->create();
        
        $data = [
            'distribution_id' => $distribution->id,
            'file_name' => 'test-document.pdf',
            'file_path' => 'distributions/test-document.pdf',
            'checksum' => 'a1b2c3d4e5f6',
            'file_size' => 1024000,
        ];

        $distributionFile = DistributionFile::create($data);

        foreach ($data as $key => $value) {
            $this->assertEquals($value, $distributionFile->$key);
        }
    }

    public function test_file_size_stores_integer()
    {
        $distributionFile = DistributionFile::factory()->create(['file_size' => 2048576]);
        
        $this->assertIsInt($distributionFile->file_size);
        $this->assertEquals(2048576, $distributionFile->file_size);
    }

    public function test_checksum_stores_string()
    {
        $checksum = 'sha256:abc123def456789ghijklmnopqrstuvwxyz';
        $distributionFile = DistributionFile::factory()->create(['checksum' => $checksum]);
        
        $this->assertIsString($distributionFile->checksum);
        $this->assertEquals($checksum, $distributionFile->checksum);
    }

    public function test_file_name_stores_string()
    {
        $fileName = 'important-document-v2-final-edited.pdf';
        $distributionFile = DistributionFile::factory()->create(['file_name' => $fileName]);
        
        $this->assertIsString($distributionFile->file_name);
        $this->assertEquals($fileName, $distributionFile->file_name);
    }

    public function test_file_path_stores_string()
    {
        $filePath = 'distributions/2024/01/15/document_abc123.pdf';
        $distributionFile = DistributionFile::factory()->create(['file_path' => $filePath]);
        
        $this->assertIsString($distributionFile->file_path);
        $this->assertEquals($filePath, $distributionFile->file_path);
    }

    public function test_belongs_to_distribution()
    {
        $distribution = Distribution::factory()->create();
        $distributionFiles = DistributionFile::factory()->count(3)->create(['distribution_id' => $distribution->id]);

        $this->assertCount(3, $distribution->files);
        
        foreach ($distributionFiles as $file) {
            $this->assertEquals($distribution->id, $file->distribution_id);
            $this->assertEquals($distribution->id, $file->distribution->id);
        }
    }

    public function test_delete_distribution_cascade_to_files()
    {
        $distribution = Distribution::factory()->create();
        $distributionFiles = DistributionFile::factory()->count(2)->create(['distribution_id' => $distribution->id]);

        $this->assertDatabaseCount('distribution_files', 2);

        $distribution->delete();

        $this->assertDatabaseCount('distribution_files', 0);
        $this->assertDatabaseMissing('distribution_files', ['distribution_id' => $distribution->id]);
    }

    public function test_file_with_null_values()
    {
        $distribution = Distribution::factory()->create();
        
        $distributionFile = DistributionFile::create([
            'distribution_id' => $distribution->id,
            'file_name' => null,
            'file_path' => null,
            'checksum' => null,
            'file_size' => null,
        ]);

        $this->assertNull($distributionFile->file_name);
        $this->assertNull($distributionFile->file_path);
        $this->assertNull($distributionFile->checksum);
        $this->assertNull($distributionFile->file_size);
    }

    public function test_factory_creates_valid_distribution_file()
    {
        $distributionFile = DistributionFile::factory()->create();

        $this->assertNotNull($distributionFile->distribution_id);
        $this->assertNotNull($distributionFile->file_name);
        $this->assertNotNull($distributionFile->file_path);
        $this->assertNotNull($distributionFile->checksum);
        $this->assertNotNull($distributionFile->file_size);
        $this->assertIsInt($distributionFile->file_size);
        $this->assertIsString($distributionFile->file_name);
        $this->assertIsString($distributionFile->file_path);
        $this->assertIsString($distributionFile->checksum);
    }

    public function test_unique_constraints()
    {
        $distribution = Distribution::factory()->create();
        
        $file1 = DistributionFile::factory()->create([
            'distribution_id' => $distribution->id,
            'file_path' => 'distributions/test.pdf'
        ]);

        // Should be able to create file with different path for same distribution
        $file2 = DistributionFile::factory()->create([
            'distribution_id' => $distribution->id,
            'file_path' => 'distributions/test2.pdf'
        ]);

        $this->assertNotEquals($file1->file_path, $file2->file_path);
        $this->assertEquals($distribution->id, $file1->distribution_id);
        $this->assertEquals($distribution->id, $file2->distribution_id);
    }

    public function test_large_file_size()
    {
        $largeSize = 2 * 1024 * 1024 * 1024; // 2GB in bytes
        $distributionFile = DistributionFile::factory()->create(['file_size' => $largeSize]);
        
        $this->assertEquals($largeSize, $distributionFile->file_size);
    }

    public function test_file_name_with_special_characters()
    {
        $specialFileName = 'documento_importanté (v2.1) [FINAL].pdf';
        $distributionFile = DistributionFile::factory()->create(['file_name' => $specialFileName]);
        
        $this->assertEquals($specialFileName, $distributionFile->file_name);
    }

    public function test_checksum_length()
    {
        $sha256Checksum = 'a1b2c3d4e5f6789012345678901234567890abcdef1234567890abcdef123456';
        $distributionFile = DistributionFile::factory()->create(['checksum' => $sha256Checksum]);
        
        $this->assertEquals(64, strlen($distributionFile->checksum));
        $this->assertEquals($sha256Checksum, $distributionFile->checksum);
    }
}