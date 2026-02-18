<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->uuid('uuid')->primary();
            $table->uuid('member_identity_uuid')->index();
            $table->uuid('store_uuid')->nullable()->index();
            $table->string('display_name', 50)->unique();
            $table->string('avatar_url')->nullable();
            $table->text('bio')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('member_identity_uuid')
                  ->references('uuid')
                  ->on('member_identities')
                  ->onDelete('cascade');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('member_profiles');
    }
};
