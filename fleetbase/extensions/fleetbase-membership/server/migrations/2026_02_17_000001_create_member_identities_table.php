<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_identities', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('user_uuid')->nullable()->index();
            $table->string('wallet_address')->unique()->index();
            $table->enum('membership_status', ['pending', 'verified', 'suspended', 'revoked'])
                  ->default('pending')
                  ->index();
            $table->timestamp('verified_at')->nullable();
            $table->string('nft_token_account')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('user_uuid')
                  ->references('uuid')
                  ->on('users')
                  ->onDelete('set null');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('member_identities');
    }
};
