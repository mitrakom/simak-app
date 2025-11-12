<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('institusis', function (Blueprint $table) {
            // Primary theme color (e.g., blue, purple, green, red, indigo)
            $table->string('theme_primary_color', 50)->default('blue')->after('feeder_password');

            // Secondary theme color
            $table->string('theme_secondary_color', 50)->default('purple')->after('theme_primary_color');

            // Accent color
            $table->string('theme_accent_color', 50)->default('indigo')->after('theme_secondary_color');

            // Logo path (optional)
            $table->string('logo_path')->nullable()->after('theme_accent_color');

            // Favicon path (optional)
            $table->string('favicon_path')->nullable()->after('logo_path');

            // Custom CSS (optional)
            $table->text('custom_css')->nullable()->after('favicon_path');

            // Theme mode: light, dark, auto
            $table->enum('theme_mode', ['light', 'dark', 'auto'])->default('auto')->after('custom_css');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('institusis', function (Blueprint $table) {
            $table->dropColumn([
                'theme_primary_color',
                'theme_secondary_color',
                'theme_accent_color',
                'logo_path',
                'favicon_path',
                'custom_css',
                'theme_mode',
            ]);
        });
    }
};
