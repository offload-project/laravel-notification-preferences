<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    public function up(): void
    {
        $tableName = config('notification-preferences.table_name', 'notification_preferences');

        /** @var class-string<Illuminate\Database\Eloquent\Model> $userModel */
        $userModel = config('notification-preferences.user_model', 'App\\Models\\User');
        $userTable = $userModel::make()->getTable();

        Schema::create($tableName, function (Blueprint $table) use ($userTable) {
            $table->id();
            $table->foreignId('user_id')->constrained($userTable)->cascadeOnDelete();
            $table->string('notification_type'); // Fully qualified class name
            $table->string('channel'); // mail, database, broadcast, etc.
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'notification_type', 'channel']);
            $table->index(['user_id', 'enabled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('notification-preferences.table_name', 'notification_preferences'));
    }
};
