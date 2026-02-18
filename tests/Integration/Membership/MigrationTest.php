<?php

namespace Tests\Integration\Membership;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_identities_table_exists()
    {
        $this->assertTrue(Schema::hasTable('member_identities'));
    }

    public function test_member_identities_table_has_correct_columns()
    {
        $this->assertTrue(Schema::hasColumn('member_identities', 'uuid'));
        $this->assertTrue(Schema::hasColumn('member_identities', 'user_uuid'));
        $this->assertTrue(Schema::hasColumn('member_identities', 'wallet_address'));
        $this->assertTrue(Schema::hasColumn('member_identities', 'membership_status'));
        $this->assertTrue(Schema::hasColumn('member_identities', 'verified_at'));
        $this->assertTrue(Schema::hasColumn('member_identities', 'nft_token_account'));
        $this->assertTrue(Schema::hasColumn('member_identities', 'last_verified_at'));
        $this->assertTrue(Schema::hasColumn('member_identities', 'metadata'));
        $this->assertTrue(Schema::hasColumn('member_identities', 'created_at'));
        $this->assertTrue(Schema::hasColumn('member_identities', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('member_identities', 'deleted_at'));
    }

    public function test_member_profiles_table_exists()
    {
        $this->assertTrue(Schema::hasTable('member_profiles'));
    }

    public function test_member_profiles_table_has_correct_columns()
    {
        $this->assertTrue(Schema::hasColumn('member_profiles', 'uuid'));
        $this->assertTrue(Schema::hasColumn('member_profiles', 'member_identity_uuid'));
        $this->assertTrue(Schema::hasColumn('member_profiles', 'store_uuid'));
        $this->assertTrue(Schema::hasColumn('member_profiles', 'display_name'));
        $this->assertTrue(Schema::hasColumn('member_profiles', 'avatar_url'));
        $this->assertTrue(Schema::hasColumn('member_profiles', 'bio'));
        $this->assertTrue(Schema::hasColumn('member_profiles', 'metadata'));
        $this->assertTrue(Schema::hasColumn('member_profiles', 'created_at'));
        $this->assertTrue(Schema::hasColumn('member_profiles', 'updated_at'));
        $this->assertTrue(Schema::hasColumn('member_profiles', 'deleted_at'));
    }

    public function test_member_identities_wallet_address_has_unique_index()
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes('member_identities');

        $uniqueIndexFound = false;
        foreach ($indexes as $index) {
            if ($index->isUnique() && in_array('wallet_address', $index->getColumns())) {
                $uniqueIndexFound = true;
                break;
            }
        }

        $this->assertTrue($uniqueIndexFound, 'wallet_address should have a unique index');
    }

    public function test_member_profiles_display_name_has_unique_index()
    {
        $indexes = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes('member_profiles');

        $uniqueIndexFound = false;
        foreach ($indexes as $index) {
            if ($index->isUnique() && in_array('display_name', $index->getColumns())) {
                $uniqueIndexFound = true;
                break;
            }
        }

        $this->assertTrue($uniqueIndexFound, 'display_name should have a unique index');
    }

    public function test_migrations_can_rollback()
    {
        Artisan::call('migrate:rollback', [
            '--path' => 'extensions/fleetbase-membership/server/migrations',
            '--step' => 2
        ]);

        $this->assertFalse(Schema::hasTable('member_profiles'));
        $this->assertFalse(Schema::hasTable('member_identities'));

        Artisan::call('migrate', [
            '--path' => 'extensions/fleetbase-membership/server/migrations'
        ]);

        $this->assertTrue(Schema::hasTable('member_identities'));
        $this->assertTrue(Schema::hasTable('member_profiles'));
    }

    public function test_foreign_key_constraint_on_member_profiles()
    {
        $foreignKeys = Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableForeignKeys('member_profiles');

        $foreignKeyFound = false;
        foreach ($foreignKeys as $foreignKey) {
            if (in_array('member_identity_uuid', $foreignKey->getLocalColumns())) {
                $foreignKeyFound = true;
                $this->assertEquals('member_identities', $foreignKey->getForeignTableName());
                break;
            }
        }

        $this->assertTrue($foreignKeyFound, 'Foreign key constraint should exist on member_identity_uuid');
    }
}
