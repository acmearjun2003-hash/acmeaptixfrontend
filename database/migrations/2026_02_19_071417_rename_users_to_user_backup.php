<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameUsersToUserBackup extends Migration
{
   public function up(): void
    {
        Schema::rename('users', 'user_backup');
    }

    public function down(): void
    {
        Schema::rename('user_backup', 'users');
    }
}
