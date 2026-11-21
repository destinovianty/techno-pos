<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->string('username')->unique()->after('name');

            $table->enum('role', [
                'kasir',
                'pic',
                'dapur',
            ])->default('kasir')->after('username');

            $table->string('outlet')->after('role');

        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {

            $table->dropColumn([
                'username',
                'role',
                'outlet',
            ]);

        });
    }
};
